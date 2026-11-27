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

namespace kim\present\libcontainer\logic;

use function in_array;

/**
 * Decides what a transaction means for a menu: buttons first, then locked slots, then free slots.
 *
 * @internal
 */
final class TransactionClassifier{
    private function __construct(){
    }

    /**
     * @param list<ActionData> $actions
     */
    public static function classify(SlotRules $rules, array $actions) : Classification{
        $buttons = $locked = $free = [];
        $touched = false;
        foreach($actions as $action){
            if($action->kind !== ActionKind::MENU_SLOT){
                continue;
            }
            $touched = true;
            $slot = $action->slot;
            switch($rules->resolve($slot)){
                case SlotMode::BUTTON:
                    self::addOnce($buttons, $slot);
                    break;
                case SlotMode::LOCKED:
                    self::addOnce($locked, $slot);
                    break;
                case SlotMode::FREE:
                    self::addOnce($free, $slot);
                    break;
            }
        }

        $outcome = match(true){
            !$touched => Outcome::IGNORE,
            $buttons !== [] => Outcome::BUTTON,
            $locked !== [] => Outcome::LOCKED,
            default => Outcome::FREE,
        };
        return new Classification($outcome, $buttons, $locked, $free);
    }

    /** @param list<int> $list */
    private static function addOnce(array &$list, int $slot) : void{
        if(!in_array($slot, $list, true)){
            $list[] = $slot;
        }
    }
}
