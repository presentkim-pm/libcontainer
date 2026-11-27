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
use pocketmine\network\mcpe\protocol\types\inventory\WindowTypes;

/**
 * Describes what a menu looks like to the client: slot count, window type and how the window is shown.
 * Immutable; build one with {@see ContainerType::builder()}.
 */
final class ContainerType{
    /**
     * Entity the container is attached to when no entity type is given.
     *
     * PROVISIONAL: which entity gives the best result (no visible model, no interaction side effects, accepted
     * by every client version) is still to be measured in game. Change it here.
     */
    public const DEFAULT_ENTITY_TYPE = "minecraft:chest_minecart";

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
        if($windowType < 0 || $windowType > 255){
            throw new InvalidArgumentException("Window type must fit in a byte (0..255), got $windowType");
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

    /** Text put in front of the title so a resource-pack JSON UI screen can recognise the window */
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
