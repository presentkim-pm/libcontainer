<?php

declare(strict_types=1);

namespace kim\present\libcontainer\tests\ui;

use InvalidArgumentException;
use kim\present\libcontainer\ui\DataSlot;
use kim\present\libcontainer\ui\Screen;
use kim\present\libcontainer\ui\Screens;
use PHPUnit\Framework\TestCase;
use pocketmine\network\mcpe\protocol\types\inventory\WindowTypes;

final class ScreenTest extends TestCase{
    public function testKeyValidation() : void{
        self::assertTrue(Screen::isValidKey("§b§w§r§s"));
        self::assertTrue(Screen::isValidKey("§1"));
        self::assertFalse(Screen::isValidKey(""));
        self::assertFalse(Screen::isValidKey("§b§w§"));
        self::assertFalse(Screen::isValidKey("§b§wshop"));
        self::assertFalse(Screen::isValidKey("shop"));
        self::assertFalse(Screen::isValidKey("§§"));
    }

    public function testPrefixIsRequiredByDefault() : void{
        $this->expectException(InvalidArgumentException::class);
        new Screen("§s§h", 54);
    }

    public function testAnyKeyCanBeAllowed() : void{
        $screen = Screen::builder()->key("§s§h")->size(10)->anyKey()->build();
        self::assertSame("§s§h", $screen->getKey());
        self::assertSame(10, $screen->getSize());
    }

    public function testSizeRange() : void{
        self::assertSame(243, (new Screen("§b§w§t§1", 243))->getSize());
        $this->expectException(InvalidArgumentException::class);
        new Screen("§b§w§t§2", 244);
    }

    public function testBuilderNeedsAKey() : void{
        $this->expectException(InvalidArgumentException::class);
        Screen::builder()->build();
    }

    public function testRegisterCreatesTheContainerType() : void{
        $type = Screens::register(new Screen("§b§w§r§1", 54));
        self::assertSame(54, $type->getSize());
        self::assertSame(WindowTypes::CONTAINER, $type->getWindowType());
        self::assertSame("§b§w§r§1", $type->getScreenKey());
        self::assertSame("§b§w§r§1Shop", $type->formatTitle("Shop"));
        self::assertTrue(Screens::has("§b§w§r§1"));
        self::assertSame($type, Screens::getType("§b§w§r§1"));
        self::assertSame(54, Screens::get("§b§w§r§1")->getSize());
    }

    public function testDuplicateKeyIsRejected() : void{
        Screens::register(new Screen("§b§w§r§2", 54));
        $this->expectException(InvalidArgumentException::class);
        Screens::register(new Screen("§b§w§r§2", 27));
    }

    public function testUnknownKeyThrows() : void{
        $this->expectException(InvalidArgumentException::class);
        Screens::getType("§b§w§r§z");
    }

    public function testDataSlotValidation() : void{
        $data = new DataSlot("100", ["a", "b"], 3);
        self::assertSame("§r100\na\nb", $data->getHoverText());
        self::assertSame([5, 10], (new DataSlot("hp", durability: [5, 10]))->durability);
    }

    public function testDataSlotRejectsBadCount() : void{
        $this->expectException(InvalidArgumentException::class);
        new DataSlot("x", count: 65);
    }

    public function testDataSlotRejectsBadDurability() : void{
        $this->expectException(InvalidArgumentException::class);
        new DataSlot("x", durability: [3, 0]);
    }

    public function testDataSlotRejectsCountWithDurability() : void{
        $this->expectException(InvalidArgumentException::class);
        new DataSlot("x", count: 2, durability: [1, 2]);
    }
}
