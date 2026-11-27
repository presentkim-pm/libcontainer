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

use function array_key_exists;

/**
 * Detects transactions that touch the same real slot twice because a bound menu slot is only a mirror of it.
 *
 * Without this, a client request "own inventory slot 2 -> menu slot 2" (valid on both sides) is applied to the same
 * slot twice and the items duplicate.
 *
 * @internal
 */
final class BindCollisions{
    private function __construct(){
    }

    /**
     * Maps a menu slot to the real slot it mirrors: [sourceId, sourceSlot], or null when no binding covers it.
     *
     * @param list<array{int, BindRange}> $ranges [sourceId, range] pairs
     * @return array{int, int}|null
     */
    public static function resolve(array $ranges, int $menuSlot) : ?array{
        foreach($ranges as [$sourceId, $range]){
            if($range->containsMenuSlot($menuSlot)){
                return [$sourceId, $range->toSource($menuSlot)];
            }
        }
        return null;
    }

    /**
     * True when two touches refer to the same underlying slot. Touches of kind MENU_SLOT are resolved through the
     * bindings (unbound menu slots are their own slot, in $menuId); every other touch is its (inventory, slot).
     *
     * @param list<array{int, BindRange}> $ranges [sourceId, range] pairs
     * @param list<SlotTouch> $touches
     */
    public static function collides(array $ranges, int $menuId, array $touches) : bool{
        $seen = [];
        foreach($touches as $touch){
            if($touch->kind === ActionKind::MENU_SLOT){
                $real = self::resolve($ranges, $touch->slot) ?? [$menuId, $touch->slot];
            }else{
                $real = [$touch->inventoryId, $touch->slot];
            }
            $key = $real[0] . ':' . $real[1];
            if(array_key_exists($key, $seen)){
                return true;
            }
            $seen[$key] = true;
        }
        return false;
    }
}
