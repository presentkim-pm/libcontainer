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
 * - paired with the cursor: SWAP when the slot held an item and still holds one (it was replaced),
 *   otherwise PICKUP (taking from or putting into an empty spot)
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
        $drop = $quickMove = $cursor = false;
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
                    $cursor = true;
                    break;
                default:
                    break;
            }
        }

        return match(true){
            $menuAction === null => ClickType::UNKNOWN,
            $drop => ClickType::DROP,
            $quickMove => ClickType::QUICK_MOVE,
            $cursor => (!$menuAction->sourceEmpty && !$menuAction->targetEmpty) ? ClickType::SWAP : ClickType::PICKUP,
            default => ClickType::UNKNOWN,
        };
    }
}
