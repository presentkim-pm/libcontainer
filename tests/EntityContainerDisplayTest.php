<?php

declare(strict_types=1);

namespace kim\present\libcontainer\tests;

use kim\present\libcontainer\ContainerType;
use kim\present\libcontainer\display\EntityContainerDisplay;
use PHPUnit\Framework\TestCase;
use pocketmine\network\mcpe\protocol\types\entity\ByteMetadataProperty;
use pocketmine\network\mcpe\protocol\types\entity\EntityMetadataFlags;
use pocketmine\network\mcpe\protocol\types\entity\EntityMetadataProperties;
use pocketmine\network\mcpe\protocol\types\entity\FloatMetadataProperty;
use pocketmine\network\mcpe\protocol\types\entity\IntMetadataProperty;
use pocketmine\network\mcpe\protocol\types\entity\LongMetadataProperty;
use pocketmine\network\mcpe\protocol\types\entity\StringMetadataProperty;
use pocketmine\network\mcpe\protocol\types\inventory\WindowTypes;

final class EntityContainerDisplayTest extends TestCase{
    public function testMetadata() : void{
        $type = ContainerType::builder()->size(54)->windowType(WindowTypes::CONTAINER)->screenKey("§k")->build();
        $meta = EntityContainerDisplay::createMetadata($type, "Shop");

        $name = $meta[EntityMetadataProperties::NAMETAG];
        self::assertInstanceOf(StringMetadataProperty::class, $name);
        self::assertSame("§kShop", $name->getValue());

        $type = $meta[EntityMetadataProperties::CONTAINER_TYPE];
        self::assertInstanceOf(ByteMetadataProperty::class, $type);
        self::assertSame(WindowTypes::CONTAINER, $type->getValue());

        $size = $meta[EntityMetadataProperties::CONTAINER_BASE_SIZE];
        self::assertInstanceOf(IntMetadataProperty::class, $size);
        self::assertSame(54, $size->getValue());

        $scale = $meta[EntityMetadataProperties::SCALE];
        self::assertInstanceOf(FloatMetadataProperty::class, $scale);
        self::assertEqualsWithDelta(EntityContainerDisplay::SCALE, $scale->getValue(), 0.0001);

        self::assertSame(0.0, $meta[EntityMetadataProperties::BOUNDING_BOX_WIDTH]->getValue());
        self::assertSame(0.0, $meta[EntityMetadataProperties::BOUNDING_BOX_HEIGHT]->getValue());

        $flags = $meta[EntityMetadataProperties::FLAGS];
        self::assertInstanceOf(LongMetadataProperty::class, $flags);
        self::assertSame(1 << EntityMetadataFlags::INVISIBLE, $flags->getValue() & (1 << EntityMetadataFlags::INVISIBLE));
        self::assertSame(1 << EntityMetadataFlags::IMMOBILE, $flags->getValue() & (1 << EntityMetadataFlags::IMMOBILE));
    }
}
