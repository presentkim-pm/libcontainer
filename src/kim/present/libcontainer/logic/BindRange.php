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

use InvalidArgumentException;

/**
 * A validated slot range of a binding: source slots [sourceStart, sourceStart + count) shown at menu slots
 * [offset, offset + count). Pure logic, no server needed.
 */
final class BindRange{
    private function __construct(
        public readonly int $offset,
        public readonly int $sourceStart,
        public readonly int $count,
    ){}

    /**
     * @param int|null $count slot count, null for everything from $sourceStart to the end of the source
     *
     * @throws InvalidArgumentException if the range does not fit in the source or the menu
     */
    public static function resolve(int $menuSize, int $sourceSize, int $offset, int $sourceStart, ?int $count) : self{
        if($offset < 0){
            throw new InvalidArgumentException("Bind offset must not be negative, got $offset");
        }
        if($sourceStart < 0 || $sourceStart >= $sourceSize){
            throw new InvalidArgumentException("Source start $sourceStart is outside the source of $sourceSize slots");
        }
        $count ??= $sourceSize - $sourceStart;
        if($count < 1){
            throw new InvalidArgumentException("Bind count must be at least 1, got $count");
        }
        if($sourceStart + $count > $sourceSize){
            throw new InvalidArgumentException("Cannot bind $count slots from $sourceStart of a source of $sourceSize slots");
        }
        if($offset + $count > $menuSize){
            throw new InvalidArgumentException("Cannot bind $count slots at offset $offset into a menu of $menuSize slots");
        }
        return new self($offset, $sourceStart, $count);
    }

    public function menuEnd() : int{
        return $this->offset + $this->count;
    }

    public function sourceEnd() : int{
        return $this->sourceStart + $this->count;
    }

    public function containsMenuSlot(int $slot) : bool{
        return $slot >= $this->offset && $slot < $this->menuEnd();
    }

    public function containsSourceSlot(int $slot) : bool{
        return $slot >= $this->sourceStart && $slot < $this->sourceEnd();
    }

    /** The source slot shown at the menu slot (the slot must be inside the range) */
    public function toSource(int $menuSlot) : int{
        return $menuSlot - $this->offset + $this->sourceStart;
    }

    /** The menu slot that shows the source slot (the slot must be inside the range) */
    public function toMenu(int $sourceSlot) : int{
        return $sourceSlot - $this->sourceStart + $this->offset;
    }

    public function overlapsMenu(self $other) : bool{
        return $this->offset < $other->menuEnd() && $other->offset < $this->menuEnd();
    }

    public function overlapsSource(self $other) : bool{
        return $this->sourceStart < $other->sourceEnd() && $other->sourceStart < $this->sourceEnd();
    }
}
