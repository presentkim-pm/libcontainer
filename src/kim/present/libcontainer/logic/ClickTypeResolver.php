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

use kim\present\libcontainer\ClickType;

/**
 * Infers how a slot was clicked from the other actions of its transaction.
 *
 * - paired with a drop action: DROP
 * - paired with a slot of the player's inventory: QUICK_MOVE
 * - paired with the cursor: SWAP when the cursor held an item and the slot held one and still holds one (the two were
 *   exchanged), otherwise PICKUP (taking from or putting into an empty spot, or a partial take such as a right
 *   click on a stack, where the cursor starts empty)
 * - anything else: UNKNOWN
 *
 * @internal
 */
final class ClickTypeResolver{
    private function __construct(){
    }

    /**
     * @param list<ActionData> $actions every action of the transaction
     */
    public static function resolve(array $actions, int $menuSlot) : ClickType{
        $menuAction = null;
        $drop = $quickMove = false;
        $cursorHeld = null; //whether the cursor held an item before, null when the transaction has no cursor action
        foreach($actions as $action){
            switch($action->kind){
                case ActionKind::MENU_SLOT:
                    if($menuAction === null && $action->slot === $menuSlot){
                        $menuAction = $action;
                    }
                    break;
                case ActionKind::DROP:
                    $drop = true;
                    break;
                case ActionKind::PLAYER_INVENTORY:
                    $quickMove = true;
                    break;
                case ActionKind::CURSOR:
                    $cursorHeld ??= !$action->sourceEmpty;
                    break;
                default:
                    break;
            }
        }

        return match(true){
            $menuAction === null => ClickType::UNKNOWN,
            $drop => ClickType::DROP,
            $quickMove => ClickType::QUICK_MOVE,
            $cursorHeld !== null => ($cursorHeld && !$menuAction->sourceEmpty && !$menuAction->targetEmpty) ? ClickType::SWAP : ClickType::PICKUP,
            default => ClickType::UNKNOWN,
        };
    }
}
