<?php

declare(strict_types=1);

namespace kim\present\libcontainer\tests;

use InvalidArgumentException;
use kim\present\libcontainer\ContainerType;
use kim\present\libcontainer\ContainerTypes;
use PHPUnit\Framework\TestCase;

final class ContainerTypesTest extends TestCase{
    public function testBuiltIns() : void{
        self::assertSame(27, ContainerTypes::get(ContainerTypes::CHEST)->getSize());
        self::assertSame(54, ContainerTypes::get(ContainerTypes::LARGE)->getSize());
        self::assertSame(243, ContainerTypes::get(ContainerTypes::FULL)->getSize());
        $hopper = ContainerTypes::get(ContainerTypes::HOPPER);
        self::assertSame(5, $hopper->getSize());
        self::assertSame(\pocketmine\network\mcpe\protocol\types\inventory\WindowTypes::HOPPER, $hopper->getWindowType());
        self::assertSame("libcontainer:hopper", ContainerTypes::HOPPER);
        self::assertSame("libcontainer:chest", ContainerTypes::CHEST);
    }

    public function testRegisterAndGet() : void{
        $type = ContainerType::builder()->size(9)->build();
        ContainerTypes::register("test:nine", $type);
        self::assertTrue(ContainerTypes::has("test:nine"));
        self::assertSame($type, ContainerTypes::get("test:nine"));
    }

    public function testDuplicateThrows() : void{
        ContainerTypes::register("test:dup", ContainerType::builder()->build());
        $this->expectException(InvalidArgumentException::class);
        ContainerTypes::register("test:dup", ContainerType::builder()->build());
    }

    public function testBuiltInCannotBeReplaced() : void{
        $this->expectException(InvalidArgumentException::class);
        ContainerTypes::register(ContainerTypes::CHEST, ContainerType::builder()->build());
    }

    public function testEmptyIdThrows() : void{
        $this->expectException(InvalidArgumentException::class);
        ContainerTypes::register("", ContainerType::builder()->build());
    }

    public function testUnknownThrows() : void{
        self::assertFalse(ContainerTypes::has("test:missing"));
        $this->expectException(InvalidArgumentException::class);
        ContainerTypes::get("test:missing");
    }
}
