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

/** What a transaction action touches, reduced to what the menu logic cares about. */
enum ActionKind{
    /** A slot of the menu being decided about */
    case MENU_SLOT;
    /** A slot of the player's own inventory */
    case PLAYER_INVENTORY;
    /** The slot of the item held on the cursor */
    case CURSOR;
    /** A slot of any other inventory (armor, off-hand, another menu, ...) */
    case OTHER_INVENTORY;
    /** The item is thrown on the ground */
    case DROP;
    /** Anything else (creative create/destroy, ...) */
    case OTHER;
}
