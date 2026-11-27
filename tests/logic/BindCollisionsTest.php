<?php

declare(strict_types=1);

namespace kim\present\libcontainer\tests\logic;

use kim\present\libcontainer\logic\ActionKind;
use kim\present\libcontainer\logic\BindCollisions;
use kim\present\libcontainer\logic\BindRange;
use kim\present\libcontainer\logic\SlotTouch;
use PHPUnit\Framework\TestCase;

final class BindCollisionsTest extends TestCase{
    private const MENU = 1;
    private const PLAYER = 28;

    /** @return list<BindRange> the player's 36 slots mirrored at menu slots 0..35 */
    private function ranges() : array{
        return [[self::PLAYER, BindRange::resolve(54, 36, 0, 0, null)]];
    }

    private function menu(int $slot) : SlotTouch{
        return new SlotTouch(ActionKind::MENU_SLOT, self::MENU, $slot);
    }

    private function player(int $slot) : SlotTouch{
        return new SlotTouch(ActionKind::PLAYER_INVENTORY, self::PLAYER, $slot);
    }

    public function testResolve() : void{
        $ranges = [[self::PLAYER, BindRange::resolve(54, 36, 0, 0, null)], [99, BindRange::resolve(54, 4, 45, 0, null)]];
        self::assertSame([self::PLAYER, 2], BindCollisions::resolve($ranges, 2));
        self::assertSame([99, 1], BindCollisions::resolve($ranges, 46));
        self::assertNull(BindCollisions::resolve($ranges, 40));
        self::assertNull(BindCollisions::resolve($ranges, 49));
    }

    public function testSelfMonitorShiftClickCollides() : void{
        //measured: PlayerInventory slot 2 -> menu slot 2
        self::assertTrue(BindCollisions::collides($this->ranges(), self::MENU, [$this->player(2), $this->menu(2)]));
    }

    public function testSwapWithinTheSameMirroredSlotCollides() : void{
        self::assertTrue(BindCollisions::collides($this->ranges(), self::MENU, [
            $this->menu(5), $this->player(5), new SlotTouch(ActionKind::CURSOR, 7, 0),
        ]));
    }

    public function testSourceStartBindingCollides() : void{
        //source slots 18..35 shown at menu slots 0..17
        $ranges = [[self::PLAYER, BindRange::resolve(27, 36, 0, 18, 18)]];
        self::assertTrue(BindCollisions::collides($ranges, self::MENU, [$this->menu(2), $this->player(20)]));
        self::assertFalse(BindCollisions::collides($ranges, self::MENU, [$this->menu(2), $this->player(2)]));
    }

    public function testOffsetBindingCollides() : void{
        $ranges = [[self::PLAYER, BindRange::resolve(54, 36, 9, 0, null)]];
        self::assertTrue(BindCollisions::collides($ranges, self::MENU, [$this->menu(11), $this->player(2)]));
        self::assertFalse(BindCollisions::collides($ranges, self::MENU, [$this->menu(11), $this->player(11)]));
    }

    public function testDifferentHotbarSlotIntoMirrorIsFine() : void{
        self::assertFalse(BindCollisions::collides($this->ranges(), self::MENU, [$this->player(3), $this->menu(2)]));
    }

    public function testDragBetweenMirrorSlotsIsFine() : void{
        self::assertFalse(BindCollisions::collides($this->ranges(), self::MENU, [$this->menu(1), $this->menu(2)]));
    }

    public function testMenuAndCursorAndUnboundSlotsAreFine() : void{
        self::assertFalse(BindCollisions::collides($this->ranges(), self::MENU, [
            $this->menu(40), new SlotTouch(ActionKind::CURSOR, 7, 0),
        ]));
        self::assertFalse(BindCollisions::collides([], self::MENU, [$this->player(2), $this->menu(2)]));
    }

    public function testSameUnboundSlotTwiceCollides() : void{
        self::assertTrue(BindCollisions::collides([], self::MENU, [$this->menu(40), $this->menu(40)]));
    }

    public function testOtherPlayersInventoryAtTheSameIndexIsFine() : void{
        self::assertFalse(BindCollisions::collides($this->ranges(), self::MENU, [
            $this->menu(2), new SlotTouch(ActionKind::PLAYER_INVENTORY, 77, 2),
        ]));
    }
}
