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

namespace kim\present\libcontainer\ui;

use InvalidArgumentException;

/** Fluent builder for {@see Screen}; validation happens in {@see build()}. */
final class ScreenBuilder{
    private ?string $key = null;
    private int $size = 54;
    private bool $strictPrefix = true;

    public function key(string $key) : self{
        $this->key = $key;
        return $this;
    }

    public function size(int $size) : self{
        $this->size = $size;
        return $this;
    }

    /** Allow a key that does not start with {@see Screen::PREFIX}. */
    public function anyKey() : self{
        $this->strictPrefix = false;
        return $this;
    }

    /** @throws InvalidArgumentException */
    public function build() : Screen{
        return new Screen($this->key ?? throw new InvalidArgumentException("A screen needs a key"), $this->size, $this->strictPrefix);
    }
}
