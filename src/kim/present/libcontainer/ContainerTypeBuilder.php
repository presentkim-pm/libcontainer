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

use kim\present\libcontainer\display\ContainerDisplay;
use pocketmine\network\mcpe\protocol\types\inventory\WindowTypes;

/** Fluent builder for {@see ContainerType}; validation happens in {@see build()}. */
final class ContainerTypeBuilder{
    private int $size = 27;
    private int $windowType = WindowTypes::CONTAINER;
    private string $entityType = ContainerType::DEFAULT_ENTITY_TYPE;
    private ?string $screenKey = null;
    private ?ContainerDisplay $display = null;

    public function size(int $size) : self{
        $this->size = $size;
        return $this;
    }

    public function windowType(int $windowType) : self{
        $this->windowType = $windowType;
        return $this;
    }

    public function entityType(string $entityType) : self{
        $this->entityType = $entityType;
        return $this;
    }

    public function screenKey(?string $screenKey) : self{
        $this->screenKey = $screenKey;
        return $this;
    }

    public function display(ContainerDisplay $display) : self{
        $this->display = $display;
        return $this;
    }

    /** @throws \InvalidArgumentException */
    public function build() : ContainerType{
        return new ContainerType($this->size, $this->windowType, $this->entityType, $this->screenKey, $this->display);
    }
}
