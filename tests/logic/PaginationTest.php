<?php

declare(strict_types=1);

namespace kim\present\libcontainer\tests\logic;

use InvalidArgumentException;
use kim\present\libcontainer\logic\Pagination;
use PHPUnit\Framework\TestCase;

final class PaginationTest extends TestCase{
    public function testPageCount() : void{
        self::assertSame(1, Pagination::pageCount(0, 10));
        self::assertSame(1, Pagination::pageCount(10, 10));
        self::assertSame(2, Pagination::pageCount(11, 10));
        self::assertSame(3, Pagination::pageCount(21, 10));
    }

    public function testClamp() : void{
        self::assertSame(0, Pagination::clamp(-3, 25, 10));
        self::assertSame(2, Pagination::clamp(9, 25, 10));
        self::assertSame(1, Pagination::clamp(1, 25, 10));
        self::assertSame(0, Pagination::clamp(4, 0, 10));
    }

    public function testWindow() : void{
        self::assertSame([0, 10], Pagination::window(0, 25, 10));
        self::assertSame([20, 5], Pagination::window(2, 25, 10));
        self::assertSame([20, 5], Pagination::window(99, 25, 10));
        self::assertSame([0, 0], Pagination::window(0, 0, 10));
    }

    public function testNavigation() : void{
        self::assertFalse(Pagination::hasPrevious(0));
        self::assertTrue(Pagination::hasPrevious(1));
        self::assertTrue(Pagination::hasNext(0, 25, 10));
        self::assertFalse(Pagination::hasNext(2, 25, 10));
        self::assertFalse(Pagination::hasNext(0, 10, 10));
    }

    public function testPerPageMustBePositive() : void{
        $this->expectException(InvalidArgumentException::class);
        Pagination::pageCount(5, 0);
    }
}
