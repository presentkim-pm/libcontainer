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

/** How a player interacted with a menu slot, inferred from the transaction. */
enum ClickType{
    /** Picked an item up to, or put the cursor item into, the slot */
    case PICKUP;
    /** Shift-click: the item moves to or from the player's inventory */
    case QUICK_MOVE;
    /** The item is dropped on the ground */
    case DROP;
    /** The cursor item replaced a different item in the slot */
    case SWAP;
    /** The transaction does not match a known pattern */
    case UNKNOWN;
}
