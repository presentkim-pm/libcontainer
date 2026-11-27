<?php

declare(strict_types=1);

namespace kim\present\libcontainer\tests\logic;

use kim\present\libcontainer\logic\ActionData;
use kim\present\libcontainer\logic\ActionKind;
use kim\present\libcontainer\logic\Outcome;
use kim\present\libcontainer\logic\SlotRules;
use kim\present\libcontainer\logic\TransactionClassifier;
use PHPUnit\Framework\TestCase;

final class TransactionClassifierTest extends TestCase{
    private function rules() : SlotRules{
        $rules = new SlotRules(9);
        $rules->unlock(4, 5);
        $rules->setButton(0);
        $rules->setButton(1);
        return $rules;
    }

    public function testNoMenuActionIsIgnored() : void{
        $result = TransactionClassifier::classify($this->rules(), [
            ActionData::of(ActionKind::PLAYER_INVENTORY, 3),
            ActionData::of(ActionKind::CURSOR, 0),
            ActionData::of(ActionKind::DROP),
        ]);
        self::assertSame(Outcome::IGNORE, $result->outcome);
        self::assertSame([], $result->buttonSlots);
    }

    public function testEmptyTransactionIsIgnored() : void{
        self::assertSame(Outcome::IGNORE, TransactionClassifier::classify($this->rules(), [])->outcome);
    }

    public function testButton() : void{
        $result = TransactionClassifier::classify($this->rules(), [
            ActionData::menu(0, false, true),
            ActionData::of(ActionKind::CURSOR, 0, true, false),
        ]);
        self::assertSame(Outcome::BUTTON, $result->outcome);
        self::assertSame([0], $result->buttonSlots);
    }

    public function testButtonWinsOverLockedAndFree() : void{
        $result = TransactionClassifier::classify($this->rules(), [
            ActionData::menu(4, true, false),
            ActionData::menu(8, false, true),
            ActionData::menu(1, false, true),
        ]);
        self::assertSame(Outcome::BUTTON, $result->outcome);
        self::assertSame([1], $result->buttonSlots);
        self::assertSame([8], $result->lockedSlots);
        self::assertSame([4], $result->freeSlots);
    }

    public function testLockedWinsOverFree() : void{
        $result = TransactionClassifier::classify($this->rules(), [
            ActionData::menu(4, true, false),
            ActionData::menu(3, true, false),
        ]);
        self::assertSame(Outcome::LOCKED, $result->outcome);
    }

    public function testFree() : void{
        $result = TransactionClassifier::classify($this->rules(), [
            ActionData::menu(5, true, false),
            ActionData::of(ActionKind::CURSOR, 0, false, true),
        ]);
        self::assertSame(Outcome::FREE, $result->outcome);
        self::assertSame([5], $result->freeSlots);
    }

    public function testOutOfRangeSlotIsLocked() : void{
        $result = TransactionClassifier::classify($this->rules(), [ActionData::menu(99, true, false)]);
        self::assertSame(Outcome::LOCKED, $result->outcome);
    }

    public function testButtonSlotsAreDeduplicatedInOrder() : void{
        $result = TransactionClassifier::classify($this->rules(), [
            ActionData::menu(1, false, true),
            ActionData::menu(0, false, true),
            ActionData::menu(1, true, false),
            ActionData::menu(0, true, false),
        ]);
        self::assertSame([1, 0], $result->buttonSlots);
    }

    public function testFreeAndLockedSlotsAreDeduplicated() : void{
        $result = TransactionClassifier::classify($this->rules(), [
            ActionData::menu(4, false, true),
            ActionData::menu(4, true, false),
            ActionData::menu(7, false, true),
            ActionData::menu(7, true, false),
        ]);
        self::assertSame([4], $result->freeSlots);
        self::assertSame([7], $result->lockedSlots);
    }
}
