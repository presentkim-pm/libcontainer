<?php

/**
 *
 *  ____                           _   _  ___
 * |  _ \ _ __ ___  ___  ___ _ __ | |_| |/ (_)_ __ ___
 * | |_) | '__/ _ \/ __|/ _ \ '_ \| __| ' /| | '_ ` _ \
 * |  __/| | |  __/\__ \  __/ | | | |_| . \| | | | | | |
 * |_|   |_|  \___||___/\___|_| |_|\__|_|\_\_|_| |_| |_|
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the MIT License. see <https://opensource.org/licenses/MIT>.
 *
 * @author       PresentKim (debe3721@gmail.com)
 * @link         https://github.com/PresentKim
 * @license      https://opensource.org/licenses/MIT MIT License
 *
 *   (\ /)
 *  ( . .) ♥
 *  c(")(")
 */

declare(strict_types=1);

namespace kim\present\libcontainer\display;

use InvalidArgumentException;
use kim\present\libcontainer\ContainerType;
use kim\present\libcontainer\Menu;
use pocketmine\entity\Entity;
use pocketmine\network\mcpe\protocol\AddActorPacket;
use pocketmine\network\mcpe\protocol\ContainerOpenPacket;
use pocketmine\network\mcpe\protocol\RemoveActorPacket;
use pocketmine\network\mcpe\protocol\types\entity\EntityMetadataCollection;
use pocketmine\network\mcpe\protocol\types\entity\EntityMetadataFlags;
use pocketmine\network\mcpe\protocol\types\entity\EntityMetadataProperties;
use pocketmine\network\mcpe\protocol\types\entity\PropertySyncData;
use pocketmine\player\Player;
use function is_finite;
use function spl_object_id;
use function sqrt;

/**
 * Shows a menu as the inventory of an invisible, tiny, packet-only entity placed near the player.
 *
 * The entity only exists on the client of the viewer: it is added right before the window opens and removed when the
 * window closes. Its container type and base size metadata decide the layout, its name tag is the window title.
 */
final class EntityContainerDisplay implements ContainerDisplay{
    /** Scale of the entity; small enough to never be noticed even if the invisible flag were ignored */
    public const SCALE = 0.01;

    /** The client closes the window when the entity is this far from the eyes or farther (measured: ~5 blocks) */
    public const MAX_REACH = 5.0;

    /** @var array<int, int> spl_object_id(Player) => runtime id of the entity currently shown to that player */
    private array $actorIds = [];

    /**
     * @param float $distance how far in front of the player's eyes the entity is placed
     * @param float $offsetY  vertical offset from the player's eyes (negative = below)
     *
     * Measured: the client closes the window when the entity is farther than about 5 blocks from the eyes (5.0 kept,
     * 5.5 closed; vertically +4.8 kept, +5.6 closed), so the length of (distance, offsetY) must stay below
     * {@see MAX_REACH}.
     *
     * @throws InvalidArgumentException if the offset is not shorter than {@see MAX_REACH}
     */
    public function __construct(
        private readonly float $distance = 1.5,
        private readonly float $offsetY = -1.0,
    ){
        $length = sqrt($distance * $distance + $offsetY * $offsetY);
        if(!is_finite($length) || $length >= self::MAX_REACH){
            throw new InvalidArgumentException("The entity must be closer than " . self::MAX_REACH . " blocks to the eyes, got $length");
        }
    }

    public function show(Player $player, Menu $menu, int $windowId) : array{
        $type = $menu->getType();
        $packets = $this->hide($player, $menu); //a stale entity, if any

        $actorId = Entity::nextRuntimeId();
        $this->actorIds[spl_object_id($player)] = $actorId;

        $position = $player->getEyePos()
            ->addVector($player->getDirectionVector()->multiply($this->distance))
            ->add(0, $this->offsetY, 0);
        $yaw = $player->getLocation()->yaw;

        $packets[] = AddActorPacket::create(
            $actorId,
            $actorId,
            $type->getEntityType(),
            $position,
            null,
            0.0,
            $yaw,
            $yaw,
            $yaw,
            [],
            self::createMetadata($type, $menu->getTitle()),
            new PropertySyncData([], []),
            [],
            null
        );
        $packets[] = ContainerOpenPacket::entityInv($windowId, $type->getWindowType(), $actorId);
        return $packets;
    }

    public function hide(Player $player, Menu $menu) : array{
        $key = spl_object_id($player);
        if(!isset($this->actorIds[$key])){
            return [];
        }
        $actorId = $this->actorIds[$key];
        unset($this->actorIds[$key]);
        return [RemoveActorPacket::create($actorId)];
    }

    /**
     * Entity metadata of the container entity.
     *
     * @return array<int, \pocketmine\network\mcpe\protocol\types\entity\MetadataProperty>
     */
    public static function createMetadata(ContainerType $type, string $title) : array{
        $meta = new EntityMetadataCollection();
        $meta->setGenericFlag(EntityMetadataFlags::INVISIBLE, true);
        $meta->setGenericFlag(EntityMetadataFlags::IMMOBILE, true);
        $meta->setString(EntityMetadataProperties::NAMETAG, $type->formatTitle($title));
        $meta->setFloat(EntityMetadataProperties::SCALE, self::SCALE);
        $meta->setFloat(EntityMetadataProperties::BOUNDING_BOX_WIDTH, 0.0);
        $meta->setFloat(EntityMetadataProperties::BOUNDING_BOX_HEIGHT, 0.0);
        $meta->setByte(EntityMetadataProperties::CONTAINER_TYPE, $type->getWindowType());
        $meta->setInt(EntityMetadataProperties::CONTAINER_BASE_SIZE, $type->getSize());
        return $meta->getAll();
    }
}
