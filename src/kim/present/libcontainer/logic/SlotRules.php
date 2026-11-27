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
use function array_fill;
use function array_keys;
use function sort;

/**
 * Decides how every slot of a menu behaves. Pure data, no server needed.
 *
 * Every slot starts LOCKED. A button always wins over the lock state, and removing the button restores the
 * lock state underneath it.
 */
final class SlotRules{
    /** @var array<int, SlotMode> LOCKED or FREE for every slot */
    private array $modes;

    /** @var array<int, true> */
    private array $buttons = [];

    public function __construct(private readonly int $size){
        if($size < 1){
            throw new InvalidArgumentException("Slot count must be at least 1, got $size");
        }
        $this->modes = array_fill(0, $size, SlotMode::LOCKED);
    }

    public function getSize() : int{
        return $this->size;
    }

    public function has(int $slot) : bool{
        return $slot >= 0 && $slot < $this->size;
    }

    public function lockAll() : void{
        $this->modes = array_fill(0, $this->size, SlotMode::LOCKED);
    }

    public function unlockAll() : void{
        $this->modes = array_fill(0, $this->size, SlotMode::FREE);
    }

    public function lock(int ...$slots) : void{
        foreach($slots as $slot){
            $this->modes[$this->assertSlot($slot)] = SlotMode::LOCKED;
        }
    }

    public function unlock(int ...$slots) : void{
        foreach($slots as $slot){
            $this->modes[$this->assertSlot($slot)] = SlotMode::FREE;
        }
    }

    public function setButton(int $slot) : void{
        $this->buttons[$this->assertSlot($slot)] = true;
    }

    public function removeButton(int $slot) : void{
        unset($this->buttons[$this->assertSlot($slot)]);
    }

    public function isButton(int $slot) : bool{
        return isset($this->buttons[$slot]);
    }

    /** @return list<int> button slots in ascending order */
    public function getButtons() : array{
        $slots = array_keys($this->buttons);
        sort($slots);
        return $slots;
    }

    /** Slots outside the menu are always LOCKED. */
    public function resolve(int $slot) : SlotMode{
        if(!$this->has($slot)){
            return SlotMode::LOCKED;
        }
        return isset($this->buttons[$slot]) ? SlotMode::BUTTON : $this->modes[$slot];
    }

    private function assertSlot(int $slot) : int{
        if(!$this->has($slot)){
            throw new InvalidArgumentException("Slot $slot is out of range 0.." . ($this->size - 1));
        }
        return $slot;
    }
}
