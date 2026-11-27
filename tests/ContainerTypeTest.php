<?php

declare(strict_types=1);

namespace kim\present\libcontainer\tests;

use InvalidArgumentException;
use kim\present\libcontainer\ContainerType;
use kim\present\libcontainer\display\ContainerDisplay;
use kim\present\libcontainer\display\EntityContainerDisplay;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use pocketmine\network\mcpe\protocol\types\inventory\WindowTypes;

final class ContainerTypeTest extends TestCase{
    public function testBuilderDefaults() : void{
        $type = ContainerType::builder()->build();
        self::assertSame(27, $type->getSize());
        self::assertSame(WindowTypes::CONTAINER, $type->getWindowType());
        self::assertSame(ContainerType::DEFAULT_ENTITY_TYPE, $type->getEntityType());
        self::assertNull($type->getScreenKey());
        self::assertInstanceOf(EntityContainerDisplay::class, $type->getDisplay());
    }

    public function testBuilderValues() : void{
        $display = $this->createStub(ContainerDisplay::class);
        $type = ContainerType::builder()
            ->size(5)
            ->windowType(WindowTypes::HOPPER)
            ->entityType("minecraft:minecart")
            ->screenKey("§b§w")
            ->display($display)
            ->build();
        self::assertSame(5, $type->getSize());
        self::assertSame(WindowTypes::HOPPER, $type->getWindowType());
        self::assertSame("minecraft:minecart", $type->getEntityType());
        self::assertSame("§b§w", $type->getScreenKey());
        self::assertSame($display, $type->getDisplay());
    }

    public function testSupportedWindowTypes() : void{
        self::assertSame([WindowTypes::CONTAINER, WindowTypes::HOPPER], ContainerType::SUPPORTED_WINDOW_TYPES);
        self::assertSame(WindowTypes::HOPPER, ContainerType::builder()->windowType(WindowTypes::HOPPER)->size(5)->build()->getWindowType());
    }

    public function testFormatTitle() : void{
        self::assertSame("Shop", ContainerType::builder()->build()->formatTitle("Shop"));
        self::assertSame("§b§wShop", ContainerType::builder()->screenKey("§b§w")->build()->formatTitle("Shop"));
    }

    #[DataProvider("validSizes")]
    public function testValidSizes(int $size) : void{
        self::assertSame($size, ContainerType::builder()->size($size)->build()->getSize());
    }

    /** @return list<array{int}> */
    public static function validSizes() : array{
        return [[ContainerType::MIN_SIZE], [5], [243], [ContainerType::MAX_SIZE]];
    }

    #[DataProvider("invalidBuilders")]
    public function testInvalid(\Closure $configure) : void{
        $this->expectException(InvalidArgumentException::class);
        $configure(ContainerType::builder())->build();
    }

    /** @return array<string, array{\Closure}> */
    public static function invalidBuilders() : array{
        return [
            "size zero" => [fn($b) => $b->size(0)],
            "size negative" => [fn($b) => $b->size(-3)],
            "size too big" => [fn($b) => $b->size(ContainerType::MAX_SIZE + 1)],
            "window type negative" => [fn($b) => $b->windowType(-1)],
            "window type over a byte" => [fn($b) => $b->windowType(256)],
            "dispenser" => [fn($b) => $b->windowType(WindowTypes::DISPENSER)],
            "dropper" => [fn($b) => $b->windowType(WindowTypes::DROPPER)],
            "hopper too big" => [fn($b) => $b->windowType(WindowTypes::HOPPER)->size(6)],
            "empty entity type" => [fn($b) => $b->entityType("")],
            "empty screen key" => [fn($b) => $b->screenKey("")],
        ];
    }
}
