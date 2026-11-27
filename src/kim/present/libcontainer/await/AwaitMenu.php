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

namespace kim\present\libcontainer\await;

use Generator;
use kim\present\libcontainer\Click;
use kim\present\libcontainer\Menu;
use pocketmine\player\Player;
use SOFe\AwaitGenerator\Await;

/**
 * Await-style helpers for menus, for use inside an {@see Await} generator:
 *
 * ```php
 * Await::f2c(function() use ($menu, $player) : \Generator{
 *     $menu->open($player);
 *     $slot = yield from AwaitMenu::choose($menu, $player); // int, or null when the menu was closed
 * });
 * ```
 *
 * Needs the optional package `sof3/await-generator` ^3; nothing else in libcontainer loads this class. Call these
 * after the menu was opened: they wait for the next click or close of that player, whichever comes first.
 * The menu's listeners are removed as soon as the wait ends. If the generator is abandoned while waiting, its
 * listeners stay until the next click or close of that player.
 */
final class AwaitMenu{
    private function __construct(){
    }

    /**
     * Resolves with the player's next click on a button of the menu, or null when they close the menu first.
     *
     * @return Generator<mixed, mixed, mixed, Click|null>
     */
    public static function nextClick(Menu $menu, Player $player) : Generator{
        return yield from Await::promise(static function(\Closure $resolve) use ($menu, $player) : void{
            $stopClick = $stopClose = static function() : void{
            };
            $done = false;
            $finish = static function(?Click $click) use (&$done, &$stopClick, &$stopClose, $resolve) : void{
                if($done){
                    return;
                }
                $done = true;
                $stopClick();
                $stopClose();
                $resolve($click);
            };
            $stopClick = $menu->listenClick(static function(Click $click) use ($player, $finish) : void{
                if($click->player === $player){
                    $finish($click);
                }
            });
            $stopClose = $menu->listenClose(static function(Player $closer) use ($player, $finish) : void{
                if($closer === $player){
                    $finish(null);
                }
            });
        });
    }

    /**
     * Resolves with the slot of the player's next click on a button, or null when they close the menu first.
     *
     * @return Generator<mixed, mixed, mixed, int|null>
     */
    public static function choose(Menu $menu, Player $player) : Generator{
        $click = yield from self::nextClick($menu, $player);
        return $click?->slot;
    }

    /**
     * Resolves (with null) when the player closes the menu, by any means except the re-open of a title change.
     *
     * @return Generator<mixed, mixed, mixed, null>
     */
    public static function closed(Menu $menu, Player $player) : Generator{
        return yield from Await::promise(static function(\Closure $resolve) use ($menu, $player) : void{
            $stop = static function() : void{
            };
            $stop = $menu->listenClose(static function(Player $closer) use ($player, $resolve, &$stop) : void{
                if($closer === $player){
                    $stop();
                    $resolve(null);
                }
            });
        });
    }

    /**
     * Closes the menu and resolves (with null) once {@see Menu::closeThen()} says the client acknowledged, so the next
     * menu can be opened right after.
     *
     * @return Generator<mixed, mixed, mixed, null>
     */
    public static function closeAndWait(Menu $menu, Player $player) : Generator{
        return yield from Await::promise(static function(\Closure $resolve) use ($menu, $player) : void{
            $menu->closeThen($player, static fn() => $resolve(null));
        });
    }
}
