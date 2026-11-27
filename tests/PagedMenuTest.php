<?php

declare(strict_types=1);

namespace kim\present\libcontainer\tests;

use InvalidArgumentException;
use kim\present\libcontainer\Menu;
use kim\present\libcontainer\PagedMenu;
use PHPUnit\Framework\TestCase;

/** Rendering needs items (the server's registry), so only the layout checks are covered here; the page maths is in PaginationTest. */
final class PagedMenuTest extends TestCase{
    public function testEmptyPagesAreEmpty() : void{
        $paged = new PagedMenu(Menu::chest(), [0, 1, 2, 3]);
        self::assertSame(4, $paged->getPerPage());
        self::assertSame(1, $paged->getPageCount());
        self::assertSame(0, $paged->getPage());
    }

    public function testNeedsContentSlots() : void{
        $this->expectException(InvalidArgumentException::class);
        new PagedMenu(Menu::chest(), []);
    }

    public function testRejectsDuplicateSlots() : void{
        $this->expectException(InvalidArgumentException::class);
        new PagedMenu(Menu::chest(), [1, 2, 1]);
    }

    public function testRejectsSlotsOutsideTheMenu() : void{
        $this->expectException(InvalidArgumentException::class);
        new PagedMenu(Menu::chest(), [0, 27]);
    }
}
