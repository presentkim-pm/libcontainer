<?php

declare(strict_types=1);

namespace kim\present\libcontainer\tests\logic;

use InvalidArgumentException;
use kim\present\libcontainer\logic\SlotMode;
use kim\present\libcontainer\logic\SlotRules;
use PHPUnit\Framework\TestCase;

final class SlotRulesTest extends TestCase{
    public function testDefaultIsLocked() : void{
        $rules = new SlotRules(9);
        for($i = 0; $i < 9; ++$i){
            self::assertSame(SlotMode::LOCKED, $rules->resolve($i));
        }
    }

    public function testOutOfRangeIsLocked() : void{
        $rules = new SlotRules(9);
        $rules->unlockAll();
        self::assertSame(SlotMode::LOCKED, $rules->resolve(-1));
        self::assertSame(SlotMode::LOCKED, $rules->resolve(9));
    }

    public function testUnlockAndLock() : void{
        $rules = new SlotRules(9);
        $rules->unlock(1, 3);
        self::assertSame(SlotMode::FREE, $rules->resolve(1));
        self::assertSame(SlotMode::LOCKED, $rules->resolve(2));
        self::assertSame(SlotMode::FREE, $rules->resolve(3));

        $rules->lock(3);
        self::assertSame(SlotMode::LOCKED, $rules->resolve(3));
    }

    public function testUnlockAllThenLockSome() : void{
        $rules = new SlotRules(5);
        $rules->unlockAll();
        $rules->lock(0, 4);
        self::assertSame([SlotMode::LOCKED, SlotMode::FREE, SlotMode::FREE, SlotMode::FREE, SlotMode::LOCKED], array_map($rules->resolve(...), range(0, 4)));

        $rules->lockAll();
        self::assertSame(SlotMode::LOCKED, $rules->resolve(2));
    }

    public function testButtonOverridesEverything() : void{
        $rules = new SlotRules(5);
        $rules->setButton(2);
        self::assertSame(SlotMode::BUTTON, $rules->resolve(2));

        $rules->unlockAll();
        self::assertSame(SlotMode::BUTTON, $rules->resolve(2));
        $rules->unlock(2);
        self::assertSame(SlotMode::BUTTON, $rules->resolve(2));
        $rules->lockAll();
        self::assertSame(SlotMode::BUTTON, $rules->resolve(2));
    }

    public function testRemoveButtonRestoresUnderlyingMode() : void{
        $rules = new SlotRules(5);
        $rules->setButton(1);
        $rules->removeButton(1);
        self::assertSame(SlotMode::LOCKED, $rules->resolve(1));

        $rules->unlock(1);
        $rules->setButton(1);
        $rules->removeButton(1);
        self::assertSame(SlotMode::FREE, $rules->resolve(1));
    }

    public function testButtonList() : void{
        $rules = new SlotRules(10);
        $rules->setButton(7);
        $rules->setButton(2);
        $rules->setButton(7);
        self::assertSame([2, 7], $rules->getButtons());
        self::assertTrue($rules->isButton(7));
        self::assertFalse($rules->isButton(3));
    }

    public function testInvalidSlotThrows() : void{
        $rules = new SlotRules(3);
        $this->expectException(InvalidArgumentException::class);
        $rules->unlock(3);
    }

    public function testInvalidSizeThrows() : void{
        $this->expectException(InvalidArgumentException::class);
        new SlotRules(0);
    }
}
