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

/**
 * Plain-data view of one action of an inventory transaction.
 *
 * @internal
 */
final readonly class ActionData{
    public function __construct(
        public ActionKind $kind,
        /** Slot index inside the touched inventory, -1 when the action has none */
        public int $slot,
        /** The slot is empty before the action */
        public bool $sourceEmpty,
        /** The slot is empty after the action */
        public bool $targetEmpty,
    ){
    }

    public static function menu(int $slot, bool $sourceEmpty, bool $targetEmpty) : self{
        return new self(ActionKind::MENU_SLOT, $slot, $sourceEmpty, $targetEmpty);
    }

    public static function of(ActionKind $kind, int $slot = -1, bool $sourceEmpty = false, bool $targetEmpty = false) : self{
        return new self($kind, $slot, $sourceEmpty, $targetEmpty);
    }
}
