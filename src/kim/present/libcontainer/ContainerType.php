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

namespace kim\present\libcontainer;

use InvalidArgumentException;
use kim\present\libcontainer\display\ContainerDisplay;
use kim\present\libcontainer\display\EntityContainerDisplay;
use function implode;
use function in_array;
use pocketmine\network\mcpe\protocol\types\inventory\WindowTypes;

/**
 * Describes what a menu looks like to the client: slot count, window type and how the window is shown.
 * Immutable; build one with {@see ContainerType::builder()}.
 */
final class ContainerType{
    /**
     * Entity the container is attached to when no entity type is given.
     *
     * Measured on Windows Bedrock 1.26.65: the window opens with any entity type tried (chest_minecart, chest_boat,
     * hopper_minecart, npc, armor_stand, pig). The pig logs a Molang error on the client, so avoid it. At scale 0.01
     * and invisible the chest minecart is never noticed.
     */
    public const DEFAULT_ENTITY_TYPE = "minecraft:chest_minecart";

    /**
     * The only window types the client was measured to open from an entity inventory.
     *
     * - CONTAINER: always the vanilla small chest screen with 27 cells, whatever the size says. More cells need a
     *   resource-pack screen, see {@see getScreenKey()}.
     * - HOPPER: the hopper screen with 5 cells.
     *
     * DISPENSER and DROPPER are rejected on purpose: the client silently ignores the open and never sends a container
     * close, so the server waits for the close acknowledgement forever and no later window opens for that player until
     * they reconnect.
     */
    public const SUPPORTED_WINDOW_TYPES = [WindowTypes::CONTAINER, WindowTypes::HOPPER];

    /** Number of cells of the hopper screen */
    public const HOPPER_SIZE = 5;

    public const MIN_SIZE = 1;
    public const MAX_SIZE = 256;

    private readonly ContainerDisplay $display;

    public function __construct(
        private readonly int $size,
        private readonly int $windowType = WindowTypes::CONTAINER,
        private readonly string $entityType = self::DEFAULT_ENTITY_TYPE,
        private readonly ?string $screenKey = null,
        ?ContainerDisplay $display = null,
    ){
        if($size < self::MIN_SIZE || $size > self::MAX_SIZE){
            throw new InvalidArgumentException("Container size must be between " . self::MIN_SIZE . " and " . self::MAX_SIZE . ", got $size");
        }
        if(!in_array($windowType, self::SUPPORTED_WINDOW_TYPES, true)){
            throw new InvalidArgumentException("Unsupported window type $windowType, the client was only measured to open " . implode(", ", self::SUPPORTED_WINDOW_TYPES) . " (CONTAINER, HOPPER) from an entity");
        }
        if($windowType === WindowTypes::HOPPER && $size > self::HOPPER_SIZE){
            throw new InvalidArgumentException("A hopper window has " . self::HOPPER_SIZE . " cells at most, got $size");
        }
        if($entityType === ""){
            throw new InvalidArgumentException("Entity type must not be empty");
        }
        if($screenKey === ""){
            throw new InvalidArgumentException("Screen key must not be empty, use null for none");
        }
        $this->display = $display ?? new EntityContainerDisplay();
    }

    public static function builder() : ContainerTypeBuilder{
        return new ContainerTypeBuilder();
    }

    /** Number of slots */
    public function getSize() : int{
        return $this->size;
    }

    /** One of {@see WindowTypes} */
    public function getWindowType() : int{
        return $this->windowType;
    }

    /** Network id of the entity the container is attached to */
    public function getEntityType() : string{
        return $this->entityType;
    }

    /**
     * Text put in front of the title so a resource-pack JSON UI screen can recognise the window.
     *
     * The vanilla CONTAINER screen shows 27 cells even for bigger sizes, so a type larger than 27 needs a resource pack
     * screen that draws the extra cells. Nothing is logged when a big type has no screen key.
     */
    public function getScreenKey() : ?string{
        return $this->screenKey;
    }

    public function getDisplay() : ContainerDisplay{
        return $this->display;
    }

    /** The title as sent to the client (screen key + title). */
    public function formatTitle(string $title) : string{
        return ($this->screenKey ?? "") . $title;
    }
}
