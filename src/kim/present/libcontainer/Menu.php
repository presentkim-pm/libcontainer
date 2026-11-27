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

use InvalidArgumentException;
use kim\present\libcontainer\logic\SlotRules;
use LogicException;
use pocketmine\inventory\Inventory;
use pocketmine\inventory\transaction\action\SlotChangeAction;
use pocketmine\inventory\transaction\InventoryTransaction;
use pocketmine\item\Item;
use pocketmine\player\Player;
use function array_values;
use function spl_object_id;

/**
 * A virtual inventory window: a container of a given {@see ContainerType} with a title, clickable buttons,
 * locked and free slots, and optional live mirrors of real inventories.
 *
 * Every slot is locked until you say otherwise. {@see LibContainer::register()} must have been called once
 * before a menu can be opened.
 */
final class Menu{
    private readonly MenuInventory $inventory;
    private readonly SlotRules $rules;
    private readonly ContainerType $type;

    /** @var array<int, \Closure(Click) : void> */
    private array $buttons = [];
    /** @var array<int, \Closure(Item) : bool> */
    private array $validators = [];
    /** @var list<\Closure(SlotChange) : bool> */
    private array $changeHandlers = [];
    /** @var list<\Closure(Player) : void> */
    private array $openHandlers = [];
    /** @var list<\Closure(Player, Menu) : void> */
    private array $closeHandlers = [];
    /** @var list<InventoryBinding> */
    private array $bindings = [];
    /** @var array<int, true> spl_object_id(Player) of viewers whose window is being re-opened (title change) */
    private array $reopening = [];

    private function __construct(ContainerType $type, private string $title){
        $this->type = $type;
        $this->rules = new SlotRules($type->getSize());
        $this->inventory = new MenuInventory($this, $type->getSize());
    }

    /**
     * @param string|ContainerType $type a {@see ContainerType} or the id of a registered one
     *
     * @throws InvalidArgumentException if the id is unknown
     */
    public static function create(string|ContainerType $type, string $title = "") : self{
        return new self($type instanceof ContainerType ? $type : ContainerTypes::get($type), $title);
    }

    /** 27 slots */
    public static function chest(string $title = "") : self{
        return self::create(ContainerTypes::CHEST, $title);
    }

    /** 54 slots */
    public static function large(string $title = "") : self{
        return self::create(ContainerTypes::LARGE, $title);
    }

    /** 243 slots */
    public static function full(string $title = "") : self{
        return self::create(ContainerTypes::FULL, $title);
    }

    //---- basics ----

    public function getInventory() : Inventory{
        return $this->inventory;
    }

    public function getType() : ContainerType{
        return $this->type;
    }

    public function getTitle() : string{
        return $this->title;
    }

    /**
     * Changes the title. Viewers get the window re-opened with the new title (the client cannot rename an open
     * window); {@see onOpen()} and {@see onClose()} handlers are not called for that re-open.
     */
    public function setTitle(string $title) : self{
        if($title === $this->title){
            return $this;
        }
        $this->title = $title;
        foreach($this->inventory->getViewers() as $viewer){
            if($viewer->getCurrentWindow() !== $this->inventory){
                continue;
            }
            $key = spl_object_id($viewer);
            $this->reopening[$key] = true;
            try{
                $viewer->removeCurrentWindow();
                $viewer->setCurrentWindow($this->inventory);
            }finally{
                unset($this->reopening[$key]);
            }
        }
        return $this;
    }

    /** @return array<int, Player> */
    public function getViewers() : array{
        return $this->inventory->getViewers();
    }

    //---- contents ----

    public function setItem(int $slot, Item $item) : self{
        $this->inventory->setItem($slot, $item);
        return $this;
    }

    public function getItem(int $slot) : Item{
        return $this->inventory->getItem($slot);
    }

    /** @param array<int, Item> $items */
    public function setContents(array $items) : self{
        $this->inventory->setContents($items);
        return $this;
    }

    /** Empties one slot, or the whole menu when no slot is given. */
    public function clear(?int $slot = null) : self{
        if($slot === null){
            $this->inventory->clearAll();
        }else{
            $this->inventory->clear($slot);
        }
        return $this;
    }

    //---- slot rules ----

    /**
     * Puts $item in the slot and makes it a button: clicking it never moves the item, and $onClick runs instead.
     *
     * The callback runs on the tick after the click (never inside the transaction event), so it may freely close or
     * open menus.
     *
     * @param \Closure(Click) : void $onClick
     */
    public function setButton(int $slot, Item $item, \Closure $onClick) : self{
        $this->rules->setButton($slot);
        $this->buttons[$slot] = $onClick;
        $this->inventory->setItem($slot, $item);
        return $this;
    }

    /** Stops the slot being a button. The item stays, and the slot goes back to its lock state. */
    public function removeButton(int $slot) : self{
        $this->rules->removeButton($slot);
        unset($this->buttons[$slot]);
        return $this;
    }

    /** Locks every slot (the default). Buttons stay buttons. Validators are dropped. */
    public function lockAll() : self{
        $this->rules->lockAll();
        $this->validators = [];
        return $this;
    }

    /** Unlocks every slot. Buttons stay buttons. */
    public function unlockAll() : self{
        $this->rules->unlockAll();
        return $this;
    }

    public function lock(int ...$slots) : self{
        $this->rules->lock(...$slots);
        foreach($slots as $slot){
            unset($this->validators[$slot]);
        }
        return $this;
    }

    public function unlock(int ...$slots) : self{
        $this->rules->unlock(...$slots);
        return $this;
    }

    /**
     * Unlocks the slot, but only items accepted by $validator may be put into it. Taking items out is always allowed.
     *
     * @param \Closure(Item) : bool $validator receives the item that would end up in the slot
     */
    public function accept(int $slot, \Closure $validator) : self{
        $this->rules->unlock($slot);
        $this->validators[$slot] = $validator;
        return $this;
    }

    //---- handlers ----

    /**
     * Called for every player change to a free slot, before it is applied. Return false to cancel the change.
     * Not called for locked slots or buttons.
     *
     * @param \Closure(SlotChange) : bool $handler
     */
    public function onChange(\Closure $handler) : self{
        $this->changeHandlers[] = $handler;
        return $this;
    }

    /** @param \Closure(Player) : void $handler */
    public function onOpen(\Closure $handler) : self{
        $this->openHandlers[] = $handler;
        return $this;
    }

    /** @param \Closure(Player, Menu) : void $handler */
    public function onClose(\Closure $handler) : self{
        $this->closeHandlers[] = $handler;
        return $this;
    }

    //---- bindings ----

    /**
     * Mirrors $source into the slots $offset .. $offset + size - 1 of the menu, in both directions and live:
     * what the player does in the menu is written to $source, what happens to $source shows up in the menu.
     * The slots become free. The menu takes over the contents of $source when binding.
     *
     * The binding keeps both inventories referenced until {@see unbind()} is called.
     *
     * @throws InvalidArgumentException if the source does not fit
     */
    public function bind(Inventory $source, int $offset = 0) : self{
        $size = $source->getSize();
        if($offset < 0 || $offset + $size > $this->type->getSize()){
            throw new InvalidArgumentException("Cannot bind $size slots at offset $offset into a menu of {$this->type->getSize()} slots");
        }
        foreach($this->bindings as $binding){
            if($binding->getSource() === $source){
                throw new InvalidArgumentException("This inventory is already bound");
            }
            if($offset < $binding->getOffset() + $binding->getSize() && $binding->getOffset() < $offset + $size){
                throw new InvalidArgumentException("Slot range overlaps another binding");
            }
        }
        for($i = 0; $i < $size; ++$i){
            $this->rules->unlock($offset + $i);
        }
        $this->bindings[] = new InventoryBinding($this->inventory, $source, $offset);
        return $this;
    }

    /** Stops mirroring $source. The slots keep their items and stay free. */
    public function unbind(Inventory $source) : self{
        foreach($this->bindings as $i => $binding){
            if($binding->getSource() === $source){
                $binding->release();
                unset($this->bindings[$i]);
            }
        }
        $this->bindings = array_values($this->bindings);
        return $this;
    }

    //---- opening and closing ----

    /**
     * Opens the menu for the player (closing their current window). Returns false when an event handler cancelled it.
     *
     * @throws LogicException if {@see LibContainer::register()} was not called
     */
    public function open(Player $player) : bool{
        if(!LibContainer::isRegistered()){
            throw new LogicException("LibContainer::register() must be called before opening a menu");
        }
        LibContainer::hook($player);
        return $player->setCurrentWindow($this->inventory);
    }

    /** Closes the menu for the player. Does nothing when they are not viewing it. */
    public function close(Player $player) : void{
        if($player->getCurrentWindow() === $this->inventory){
            $player->removeCurrentWindow();
        }
    }

    /**
     * Closes the menu, then runs $next once the client has acknowledged the close.
     *
     * The server holds back any window opened after a close until the client confirms the close, so opening another
     * menu in $next is always safe. $next runs on the tick after the acknowledgement arrived, or after 2 seconds if
     * none arrives, or on the next tick if the player was not viewing this menu. It does not run when the player has
     * disconnected.
     *
     * @param \Closure() : void $next
     */
    public function closeThen(Player $player, \Closure $next) : void{
        $viewing = $player->getCurrentWindow() === $this->inventory;
        if($viewing){
            $player->removeCurrentWindow();
        }
        LibContainer::afterClose($player, $next, $viewing);
    }

    //---- internal, used by the listener ----

    /** @internal */
    public function getRules() : SlotRules{
        return $this->rules;
    }

    /** @internal */
    public function handleOpened(Player $player) : void{
        if(isset($this->reopening[spl_object_id($player)])){
            return;
        }
        foreach($this->openHandlers as $handler){
            $handler($player);
        }
    }

    /** @internal */
    public function handleClosed(Player $player) : void{
        if(isset($this->reopening[spl_object_id($player)])){
            return;
        }
        foreach($this->closeHandlers as $handler){
            $handler($player, $this);
        }
    }

    /**
     * Builds the click for a button slot, to be run later with {@see runButton()}.
     *
     * @internal
     */
    public function createClick(Player $player, int $slot, ClickType $clickType, InventoryTransaction $transaction) : ?Click{
        return isset($this->buttons[$slot]) ? new Click($player, $this, $slot, $this->inventory->getItem($slot), $clickType, $transaction) : null;
    }

    /** @internal */
    public function runButton(Click $click) : void{
        if(isset($this->buttons[$click->slot])){
            ($this->buttons[$click->slot])($click);
        }
    }

    /**
     * Runs validators and change handlers over free-slot changes.
     *
     * @param list<SlotChangeAction> $changes
     *
     * @return bool false if the transaction must be cancelled
     * @internal
     */
    public function acceptChanges(Player $player, InventoryTransaction $transaction, array $changes) : bool{
        foreach($changes as $action){
            $slot = $action->getSlot();
            $after = $action->getTargetItem();
            if(!$after->isNull() && isset($this->validators[$slot]) && !($this->validators[$slot])($after)){
                return false;
            }
        }
        foreach($changes as $action){
            $change = new SlotChange($player, $this, $action->getSlot(), $action->getSourceItem(), $action->getTargetItem(), $action, $transaction);
            foreach($this->changeHandlers as $handler){
                if($handler($change) === false){
                    return false;
                }
            }
        }
        return true;
    }
}
