<?php

declare(strict_types=1);

namespace kim\present\libcontainer\tests;

use InvalidArgumentException;
use kim\present\libcontainer\Layout;
use PHPUnit\Framework\TestCase;

final class LayoutTest extends TestCase{
    public function testSlotAndCell() : void{
        self::assertSame(0, Layout::slot(0, 0));
        self::assertSame(13, Layout::slot(1, 4));
        self::assertSame(26, Layout::slot(2, 8));
        self::assertSame(7, Layout::slot(1, 2, 5));
        self::assertSame([1, 4], Layout::cell(13));
        self::assertSame([2, 1], Layout::cell(7, 3));
    }

    public function testSlotRejectsOutsideTheGrid() : void{
        $this->expectException(InvalidArgumentException::class);
        Layout::slot(0, 9);
    }

    public function testRowsAndRanges() : void{
        self::assertSame(3, Layout::rows(27));
        self::assertSame(2, Layout::rows(10, 9));
        self::assertSame([3, 4, 5], Layout::range(3, 5));
        self::assertSame([9, 10, 11, 12, 13, 14, 15, 16, 17], Layout::row(1));
        self::assertSame([2, 11, 20], Layout::column(2, 3));
        self::assertSame([10, 11, 19, 20], Layout::rect(1, 1, 2, 2));
        self::assertSame([], Layout::rect(0, 0, 0, 3));
    }

    public function testBorderSlots() : void{
        self::assertSame([0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 17, 18, 19, 20, 21, 22, 23, 24, 25, 26], Layout::borderSlots(3));
        self::assertCount(18, Layout::borderSlots(2));
        self::assertCount(26, Layout::borderSlots(6));
    }

    public function testInnerSlotsAreTheComplementOfTheBorder() : void{
        self::assertSame([10, 11, 12, 13, 14, 15, 16], Layout::innerSlots(3));
        self::assertSame([], Layout::innerSlots(2));
        $all = array_merge(Layout::borderSlots(6), Layout::innerSlots(6));
        sort($all);
        self::assertSame(range(0, 53), $all);
    }

    public function testInvalidWidthThrows() : void{
        $this->expectException(InvalidArgumentException::class);
        Layout::rows(10, 0);
    }
}
