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
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class BindRangeTest extends TestCase{
    public function testDefaultsCoverWholeSource() : void{
        $range = BindRange::resolve(54, 36, 0, 0, null);
        self::assertSame(36, $range->count);
        self::assertSame(36, $range->menuEnd());
        self::assertSame(36, $range->sourceEnd());
    }

    public function testCountDefaultsToTheRestOfTheSource() : void{
        $range = BindRange::resolve(27, 36, 9, 18, null);
        self::assertSame(18, $range->count);
        self::assertSame(27, $range->menuEnd());
    }

    public function testMapping() : void{
        $range = BindRange::resolve(27, 36, 9, 18, 9);
        self::assertTrue($range->containsMenuSlot(9));
        self::assertTrue($range->containsMenuSlot(17));
        self::assertFalse($range->containsMenuSlot(18));
        self::assertFalse($range->containsMenuSlot(8));
        self::assertTrue($range->containsSourceSlot(18));
        self::assertFalse($range->containsSourceSlot(27));
        self::assertSame(18, $range->toSource(9));
        self::assertSame(26, $range->toSource(17));
        self::assertSame(13, $range->toMenu(22));
    }

    /** @return iterable<string, array{int, int, int, int, ?int}> */
    public static function invalid() : iterable{
        yield "negative offset" => [27, 36, -1, 0, 1];
        yield "negative source start" => [27, 36, 0, -1, 1];
        yield "source start past the end" => [27, 36, 0, 36, null];
        yield "zero count" => [27, 36, 0, 0, 0];
        yield "count past the source" => [27, 36, 0, 30, 7];
        yield "past the menu" => [27, 36, 10, 0, 18];
        yield "whole source too big for the menu" => [27, 36, 0, 0, null];
    }

    #[DataProvider("invalid")]
    public function testInvalidRanges(int $menu, int $source, int $offset, int $start, ?int $count) : void{
        $this->expectException(InvalidArgumentException::class);
        BindRange::resolve($menu, $source, $offset, $start, $count);
    }

    public function testOverlaps() : void{
        $a = BindRange::resolve(54, 36, 0, 0, 18);
        $b = BindRange::resolve(54, 36, 18, 18, 18);
        $c = BindRange::resolve(54, 36, 17, 17, 2);
        self::assertFalse($a->overlapsMenu($b));
        self::assertFalse($a->overlapsSource($b));
        self::assertTrue($a->overlapsMenu($c));
        self::assertTrue($b->overlapsSource($c));
        self::assertTrue($c->overlapsMenu($c));
    }
}
