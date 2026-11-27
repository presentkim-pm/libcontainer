<?php

declare(strict_types=1);

namespace kim\present\libcontainer\tests\logic;

use kim\present\libcontainer\ClickType;
use kim\present\libcontainer\logic\ActionData;
use kim\present\libcontainer\logic\ActionKind;
use kim\present\libcontainer\logic\ClickTypeResolver;
use PHPUnit\Framework\TestCase;

final class ClickTypeResolverTest extends TestCase{
    public function testPickupFromButton() : void{
        self::assertSame(ClickType::PICKUP, ClickTypeResolver::resolve([
            ActionData::menu(3, false, true),
            ActionData::of(ActionKind::CURSOR, 0, true, false),
        ], 3));
    }

    public function testPlaceOntoEmptySlotIsPickup() : void{
        self::assertSame(ClickType::PICKUP, ClickTypeResolver::resolve([
            ActionData::menu(3, true, false),
            ActionData::of(ActionKind::CURSOR, 0, false, true),
        ], 3));
    }

    public function testReplacingIsSwap() : void{
        self::assertSame(ClickType::SWAP, ClickTypeResolver::resolve([
            ActionData::menu(3, false, false),
            ActionData::of(ActionKind::CURSOR, 0, false, false),
        ], 3));
    }

    public function testMeasuredTap() : void{
        self::assertSame(ClickType::PICKUP, ClickTypeResolver::resolve([
            ActionData::menu(3, false, true),
            ActionData::of(ActionKind::CURSOR, 0, true, false),
        ], 3));
    }

    public function testMeasuredShiftClick() : void{
        self::assertSame(ClickType::QUICK_MOVE, ClickTypeResolver::resolve([
            ActionData::menu(3, false, true),
            ActionData::of(ActionKind::PLAYER_INVENTORY, 9, true, false),
        ], 3));
    }

    public function testMeasuredRightClickPartialIsPickup() : void{
        //menu slot x4 -> x2, cursor air -> x2
        self::assertSame(ClickType::PICKUP, ClickTypeResolver::resolve([
            ActionData::menu(3, false, false),
            ActionData::of(ActionKind::CURSOR, 0, true, false),
        ], 3));
    }

    public function testMeasuredQDrop() : void{
        //menu slot x5 -> x4 plus a drop action
        self::assertSame(ClickType::DROP, ClickTypeResolver::resolve([
            ActionData::of(ActionKind::DROP),
            ActionData::menu(3, false, false),
        ], 3));
    }

    public function testMeasuredSwap() : void{
        //cursor Stone x64 -> paper x6, menu slot paper x6 -> Stone x64
        self::assertSame(ClickType::SWAP, ClickTypeResolver::resolve([
            ActionData::of(ActionKind::CURSOR, 0, false, false),
            ActionData::menu(3, false, false),
        ], 3));
    }

    public function testQuickMoveOutOfMenu() : void{
        self::assertSame(ClickType::QUICK_MOVE, ClickTypeResolver::resolve([
            ActionData::menu(3, false, true),
            ActionData::of(ActionKind::PLAYER_INVENTORY, 12, true, false),
        ], 3));
    }

    public function testQuickMoveIntoMenu() : void{
        self::assertSame(ClickType::QUICK_MOVE, ClickTypeResolver::resolve([
            ActionData::of(ActionKind::PLAYER_INVENTORY, 12, false, true),
            ActionData::menu(3, true, false),
        ], 3));
    }

    public function testDrop() : void{
        self::assertSame(ClickType::DROP, ClickTypeResolver::resolve([
            ActionData::menu(3, false, true),
            ActionData::of(ActionKind::DROP),
        ], 3));
    }

    public function testDropWinsOverCursor() : void{
        self::assertSame(ClickType::DROP, ClickTypeResolver::resolve([
            ActionData::menu(3, false, true),
            ActionData::of(ActionKind::CURSOR, 0, true, false),
            ActionData::of(ActionKind::DROP),
        ], 3));
    }

    public function testUnpairedIsUnknown() : void{
        self::assertSame(ClickType::UNKNOWN, ClickTypeResolver::resolve([ActionData::menu(3, false, true)], 3));
        self::assertSame(ClickType::UNKNOWN, ClickTypeResolver::resolve([
            ActionData::menu(3, false, true),
            ActionData::of(ActionKind::OTHER_INVENTORY, 1),
        ], 3));
    }

    public function testSlotWithoutActionIsUnknown() : void{
        self::assertSame(ClickType::UNKNOWN, ClickTypeResolver::resolve([
            ActionData::menu(3, false, true),
            ActionData::of(ActionKind::CURSOR, 0, true, false),
        ], 4));
    }
}
