<?php

declare(strict_types=1);

namespace kim\present\libcontainer\tests;

use InvalidArgumentException;
use kim\present\libcontainer\ContainerType;
use kim\present\libcontainer\Menu;
use kim\present\libcontainer\logic\SlotMode;
use PHPUnit\Framework\TestCase;
use pocketmine\inventory\SimpleInventory;

/** Menu construction and configuration; opening needs a player and a server, so it is not covered here. */
final class MenuTest extends TestCase{
    public function testShortcuts() : void{
        self::assertSame(27, Menu::chest()->getInventory()->getSize());
        self::assertSame(54, Menu::large("Title")->getInventory()->getSize());
        self::assertSame(243, Menu::full()->getInventory()->getSize());
        self::assertSame("Title", Menu::large("Title")->getTitle());
    }

    public function testCreateFromTypeAndId() : void{
        $type = ContainerType::builder()->size(5)->build();
        self::assertSame($type, Menu::create($type)->getType());
        self::assertSame(54, Menu::create("libcontainer:large")->getType()->getSize());
    }

    public function testUnknownTypeIdThrows() : void{
        $this->expectException(InvalidArgumentException::class);
        Menu::create("nope:nope");
    }

    public function testSlotsAreLockedByDefault() : void{
        $rules = Menu::chest()->getRules();
        self::assertSame(SlotMode::LOCKED, $rules->resolve(0));
        self::assertSame(SlotMode::LOCKED, $rules->resolve(26));
    }

    public function testRuleShortcuts() : void{
        $menu = Menu::chest()->unlockAll()->lock(0, 1)->accept(2, fn() => true);
        $rules = $menu->getRules();
        self::assertSame(SlotMode::LOCKED, $rules->resolve(0));
        self::assertSame(SlotMode::FREE, $rules->resolve(2));
        self::assertSame(SlotMode::FREE, $rules->resolve(10));
        $menu->lockAll();
        self::assertSame(SlotMode::LOCKED, $rules->resolve(10));
    }

    /*
     * Binding copies items, which needs the server's item registry (native extensions), so only the checks that
     * run before anything is copied are tested here.
     */
    public function testBindMustFit() : void{
        $this->expectException(InvalidArgumentException::class);
        Menu::chest()->bind(new SimpleInventory(9), 19);
    }

    public function testBindRejectsNegativeOffset() : void{
        $this->expectException(InvalidArgumentException::class);
        Menu::chest()->bind(new SimpleInventory(2), -1);
    }
}
