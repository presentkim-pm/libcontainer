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

use kim\present\libcontainer\logic\OpenRetry;
use pocketmine\inventory\Inventory;
use pocketmine\network\mcpe\InventoryManager;
use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\scheduler\ClosureTask;
use WeakMap;
use function array_values;

/**
 * Entry point of the library. Call {@see register()} once from your plugin's onEnable().
 */
final class LibContainer{
    private static ?PluginBase $plugin = null;
    private static ?MenuListener $listener = null;

    /** @var WeakMap<InventoryManager, true>|null managers whose container-open callbacks are already hooked */
    private static ?WeakMap $hooked = null;

    private static ?OpenRetry $openRetry = null;

    private function __construct(){
    }

    /**
     * Configures the retry of a window the client rejected. The client rejects a window that arrives while its chat
     * or pause screen is still open (typical when a command handler opens a menu in the same tick), and the open is
     * retried after $delays ticks. Defaults: 4 attempts after 5, 10, 20 and 40 ticks. The first open is never
     * delayed. If fewer delays than attempts are given the last one repeats. 0 attempts turns retrying off.
     *
     * @throws \InvalidArgumentException on a negative attempt count, a delay below 1 or no delay with attempts > 0
     */
    public static function setOpenRetry(int $attempts, int ...$delays) : void{
        self::$openRetry = new OpenRetry($attempts, $delays === [] ? OpenRetry::DEFAULT_DELAYS : array_values($delays));
    }

    /** @internal */
    public static function getOpenRetry() : OpenRetry{
        return self::$openRetry ??= new OpenRetry();
    }

    /** @internal */
    public static function cancelReopen(Player $player, Menu $menu) : void{
        self::$listener?->cancelReopen($player, $menu);
    }

    /**
     * Registers the event listener and hooks the container-open callbacks of every player. Idempotent: calls after the
     * first are ignored (while the plugin that registered first is enabled).
     */
    public static function register(PluginBase $plugin) : void{
        if(self::isRegistered()){
            return;
        }
        self::$plugin = $plugin;
        self::$hooked ??= new WeakMap();
        self::$listener = new MenuListener($plugin);
        $plugin->getServer()->getPluginManager()->registerEvents(self::$listener, $plugin);
        foreach($plugin->getServer()->getOnlinePlayers() as $player){
            self::hook($player);
        }
    }

    public static function isRegistered() : bool{
        return self::$plugin !== null && self::$plugin->isEnabled();
    }

    /**
     * Makes the player's network session open {@see MenuInventory} windows through their {@see ContainerDisplay}.
     * Safe to call repeatedly.
     *
     * @internal
     */
    public static function hook(Player $player) : void{
        $manager = $player->getNetworkSession()->getInvManager();
        self::$hooked ??= new WeakMap();
        if($manager === null || isset(self::$hooked[$manager])){
            return;
        }
        self::$hooked[$manager] = true;
        $manager->getContainerOpenCallbacks()->add(static function(int $windowId, Inventory $inventory) use ($player) : ?array{
            if(!$inventory instanceof MenuInventory){
                return null;
            }
            $menu = $inventory->getMenu();
            return $menu->getType()->getDisplay()->show($player, $menu, $windowId);
        });
    }

    /**
     * Runs $closure on the next tick.
     *
     * @param \Closure() : void $closure
     * @internal
     */
    public static function defer(\Closure $closure) : void{
        self::$plugin?->getScheduler()->scheduleDelayedTask(new ClosureTask($closure), 1);
    }

    /**
     * @param \Closure() : void $next
     * @internal
     */
    public static function afterClose(Player $player, \Closure $next, bool $awaitAck) : void{
        if(self::$listener === null){
            return;
        }
        self::$listener->afterClose($player, $next, $awaitAck);
    }
}
