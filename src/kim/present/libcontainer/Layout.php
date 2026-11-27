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

use InvalidArgumentException;
use pocketmine\item\Item;
use function array_merge;
use function array_unique;
use function array_values;
use function intdiv;
use function range;
use function sort;

/**
 * Slot arithmetic for grid-shaped menus: a chest is 9 columns wide, a resource-pack screen may use another width.
 * Everything except {@see border()} and {@see fill()} is pure and needs no server.
 */
final class Layout{
    public const DEFAULT_WIDTH = 9;

    private function __construct(){
    }

    /** Slot index of a cell, both counted from 0. */
    public static function slot(int $row, int $col, int $width = self::DEFAULT_WIDTH) : int{
        self::assertWidth($width);
        if($row < 0 || $col < 0 || $col >= $width){
            throw new InvalidArgumentException("Cell ($row, $col) is outside a grid of width $width");
        }
        return $row * $width + $col;
    }

    /** @return array{int, int} [row, col] of a slot */
    public static function cell(int $slot, int $width = self::DEFAULT_WIDTH) : array{
        self::assertWidth($width);
        if($slot < 0){
            throw new InvalidArgumentException("Slot must not be negative, got $slot");
        }
        return [intdiv($slot, $width), $slot % $width];
    }

    /** Number of rows needed for $size slots (the last row may be partial). */
    public static function rows(int $size, int $width = self::DEFAULT_WIDTH) : int{
        self::assertWidth($width);
        return intdiv($size + $width - 1, $width);
    }

    /**
     * Slots from $from to $to, both included. Counts down when $to is smaller.
     *
     * @return list<int>
     */
    public static function range(int $from, int $to) : array{
        return range($from, $to);
    }

    /** @return list<int> every slot of one row */
    public static function row(int $row, int $width = self::DEFAULT_WIDTH) : array{
        return self::rect($row, 0, 1, $width, $width);
    }

    /** @return list<int> the slots of one column, top to bottom, over $rows rows */
    public static function column(int $col, int $rows, int $width = self::DEFAULT_WIDTH) : array{
        return self::rect(0, $col, $rows, 1, $width);
    }

    /**
     * Slots of a rectangle, row by row.
     *
     * @return list<int>
     */
    public static function rect(int $row, int $col, int $rows, int $cols, int $width = self::DEFAULT_WIDTH) : array{
        if($rows < 0 || $cols < 0){
            throw new InvalidArgumentException("A rectangle cannot have a negative size");
        }
        $slots = [];
        for($r = 0; $r < $rows; ++$r){
            for($c = 0; $c < $cols; ++$c){
                $slots[] = self::slot($row + $r, $col + $c, $width);
            }
        }
        return $slots;
    }

    /**
     * Slots on the edge of a grid of $rows x $width cells, ascending. A grid of 1 or 2 rows is all border.
     *
     * @return list<int>
     */
    public static function borderSlots(int $rows, int $width = self::DEFAULT_WIDTH) : array{
        self::assertWidth($width);
        if($rows < 1){
            throw new InvalidArgumentException("A grid needs at least 1 row, got $rows");
        }
        if($rows <= 2 || $width <= 2){
            return range(0, $rows * $width - 1);
        }
        $slots = array_merge(self::row(0, $width), self::column(0, $rows, $width), self::column($width - 1, $rows, $width), self::row($rows - 1, $width));
        $slots = array_values(array_unique($slots));
        sort($slots);
        return $slots;
    }

    /** @return list<int> the cells inside the border, row by row (empty when there are none) */
    public static function innerSlots(int $rows, int $width = self::DEFAULT_WIDTH) : array{
        return $rows <= 2 || $width <= 2 ? [] : self::rect(1, 1, $rows - 2, $width - 2, $width);
    }

    /**
     * Puts $item on the edge of the menu, assuming its slots form rows of $width cells. $rows defaults to the rows
     * the menu's size fills; a partial last row is ignored.
     */
    public static function border(Menu $menu, Item $item, int $width = self::DEFAULT_WIDTH, ?int $rows = null) : void{
        $rows ??= intdiv($menu->getType()->getSize(), $width);
        self::fill($menu, $item, self::borderSlots($rows, $width));
    }

    /**
     * Puts a copy of $item in each slot.
     *
     * @param iterable<int> $slots
     */
    public static function fill(Menu $menu, Item $item, iterable $slots) : void{
        foreach($slots as $slot){
            $menu->setItem($slot, clone $item);
        }
    }

    private static function assertWidth(int $width) : void{
        if($width < 1){
            throw new InvalidArgumentException("Grid width must be at least 1, got $width");
        }
    }
}
