<?php

/**
 *
 *  ____                           _   _  ___
 * |  _ \ _ __ ___  ___  ___ _ __ | |_| |/ (_)_ __ ___
 * | |_) | '__/ _ \/ __|/ _ \ '_ \| __| ' /| | '_ ` _ \
 * |  __/| | |  __/\__ \  __/ | | | |_| . \| | | | | | |
 * |_|   |_|  \___||___/\___|_| |_|\__|_|\_\_|_| |_| |_|
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the MIT License. see <https://opensource.org/licenses/MIT>.
 *
 * @author       PresentKim (debe3721@gmail.com)
 * @link         https://github.com/PresentKim
 * @license      https://opensource.org/licenses/MIT MIT License
 *
 *   (\ /)
 *  ( . .) ♥
 *  c(")(")
 */

declare(strict_types=1);

namespace kim\present\libcontainer;

use kim\present\libcontainer\logic\ActionData;
use kim\present\libcontainer\logic\ActionKind;
use kim\present\libcontainer\logic\ClickTypeResolver;
use kim\present\libcontainer\logic\Outcome;
use kim\present\libcontainer\logic\TransactionClassifier;
use pocketmine\event\inventory\InventoryCloseEvent;
use pocketmine\event\inventory\InventoryTransactionEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerDeathEvent;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\event\player\PlayerTeleportEvent;
use pocketmine\event\server\DataPacketReceiveEvent;
use pocketmine\inventory\PlayerCursorInventory;
use pocketmine\inventory\PlayerInventory;
use pocketmine\inventory\transaction\action\DropItemAction;
use pocketmine\inventory\transaction\action\SlotChangeAction;
use pocketmine\network\mcpe\protocol\ContainerClosePacket;
use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\scheduler\ClosureTask;
use function array_values;
use function spl_object_id;

/**
 * The single event listener of the library.
 *
 * @internal
 */
final class MenuListener implements Listener{
    /** Ticks to wait for the client's close acknowledgement before running the closeThen() callbacks anyway */
    private const CLOSE_ACK_TIMEOUT = 40;

    /** @var array<int, list<\Closure() : void>> spl_object_id(Player) => callbacks waiting for the close ack */
    private array $afterClose = [];

    public function __construct(private readonly PluginBase $plugin){
    }

    public function onJoin(PlayerJoinEvent $event) : void{
        LibContainer::hook($event->getPlayer());
    }

    public function onQuit(PlayerQuitEvent $event) : void{
        unset($this->afterClose[spl_object_id($event->getPlayer())]);
    }

    /** @priority MONITOR */
    public function onTeleport(PlayerTeleportEvent $event) : void{
        $this->closeMenu($event->getPlayer());
    }

    /** @priority MONITOR */
    public function onDeath(PlayerDeathEvent $event) : void{
        $this->closeMenu($event->getPlayer());
    }

    /**
     * Window closed (by the client, by the server, on quit or on shutdown): tell the client to forget the entity and
     * notify the menu.
     */
    public function onClose(InventoryCloseEvent $event) : void{
        $inventory = $event->getInventory();
        if(!$inventory instanceof MenuInventory){
            return;
        }
        $player = $event->getPlayer();
        $menu = $inventory->getMenu();
        if($player->isConnected()){
            foreach($menu->getType()->getDisplay()->hide($player, $menu) as $packet){
                $player->getNetworkSession()->sendDataPacket($packet);
            }
        }else{
            $menu->getType()->getDisplay()->hide($player, $menu); //only forgets the state
        }
        $menu->handleClosed($player);
    }

    /**
     * Runs before the server handles the packet, so the callbacks are scheduled for the tick after the close
     * acknowledgement was processed.
     *
     * @priority MONITOR
     */
    public function onPacket(DataPacketReceiveEvent $event) : void{
        if(!$event->getPacket() instanceof ContainerClosePacket){
            return;
        }
        $player = $event->getOrigin()->getPlayer();
        if($player !== null && isset($this->afterClose[spl_object_id($player)])){
            LibContainer::defer(fn() => $this->flush($player));
        }
    }

    /**
     * Priority HIGH: plugins at lower priorities can still change the outcome, plugins at HIGHEST and MONITOR see
     * the final state (including our cancellation).
     *
     * @priority HIGH
     */
    public function onTransaction(InventoryTransactionEvent $event) : void{
        $transaction = $event->getTransaction();
        $player = $transaction->getSource();

        /** @var array<int, Menu> $menus */
        $menus = [];
        /** @var list<array{Menu|null, ActionData, SlotChangeAction|null}> $entries */
        $entries = [];
        foreach($transaction->getActions() as $action){
            if($action instanceof SlotChangeAction){
                $inventory = $action->getInventory();
                $menu = null;
                if($inventory instanceof MenuInventory){
                    $menu = $inventory->getMenu();
                    $menus[spl_object_id($menu)] = $menu;
                    $kind = ActionKind::MENU_SLOT;
                }elseif($inventory instanceof PlayerCursorInventory){
                    $kind = ActionKind::CURSOR;
                }elseif($inventory instanceof PlayerInventory){
                    $kind = ActionKind::PLAYER_INVENTORY;
                }else{
                    $kind = ActionKind::OTHER_INVENTORY;
                }
                $entries[] = [$menu, ActionData::of($kind, $action->getSlot(), $action->getSourceItem()->isNull(), $action->getTargetItem()->isNull()), $action];
            }elseif($action instanceof DropItemAction){
                $entries[] = [null, ActionData::of(ActionKind::DROP), null];
            }else{
                $entries[] = [null, ActionData::of(ActionKind::OTHER), null];
            }
        }
        if($menus === []){
            return;
        }

        foreach($menus as $menu){
            //actions on another menu's inventory are just "other inventory" for this one
            $data = [];
            $slotActions = [];
            foreach($entries as [$owner, $datum, $action]){
                if($owner !== null && $owner !== $menu){
                    $datum = ActionData::of(ActionKind::OTHER_INVENTORY, $datum->slot, $datum->sourceEmpty, $datum->targetEmpty);
                }
                $data[] = $datum;
                if($owner === $menu && $action !== null){
                    $slotActions[] = $action;
                }
            }

            $result = TransactionClassifier::classify($menu->getRules(), $data);
            switch($result->outcome){
                case Outcome::IGNORE:
                    break;
                case Outcome::BUTTON:
                    $event->cancel();
                    foreach($result->buttonSlots as $slot){
                        $click = $menu->createClick($player, $slot, ClickTypeResolver::resolve($data, $slot), $transaction);
                        if($click !== null){
                            LibContainer::defer(function() use ($menu, $click, $player) : void{
                                if($player->isConnected()){
                                    $menu->runButton($click);
                                }
                            });
                        }
                    }
                    break;
                case Outcome::LOCKED:
                    $event->cancel();
                    break;
                case Outcome::FREE:
                    if(!$menu->acceptChanges($player, $transaction, array_values($slotActions))){
                        $event->cancel();
                    }
                    break;
            }
        }
    }

    /**
     * @param \Closure() : void $next
     */
    public function afterClose(Player $player, \Closure $next, bool $awaitAck) : void{
        if(!$awaitAck){
            LibContainer::defer(function() use ($player, $next) : void{
                if($player->isConnected()){
                    $next();
                }
            });
            return;
        }
        $key = spl_object_id($player);
        $first = !isset($this->afterClose[$key]);
        $this->afterClose[$key][] = function() use ($player, $next) : void{
            if($player->isConnected()){
                $next();
            }
        };
        if($first){
            $this->plugin->getScheduler()->scheduleDelayedTask(new ClosureTask(fn() => $this->flush($player)), self::CLOSE_ACK_TIMEOUT);
        }
    }

    private function flush(Player $player) : void{
        $key = spl_object_id($player);
        $callbacks = $this->afterClose[$key] ?? [];
        unset($this->afterClose[$key]);
        foreach($callbacks as $callback){
            $callback();
        }
    }

    private function closeMenu(Player $player) : void{
        if($player->getCurrentWindow() instanceof MenuInventory){
            $player->removeCurrentWindow();
        }
    }
}
