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
use kim\present\libcontainer\ContainerType;
use function preg_match;
use function str_starts_with;

/**
 * Definition of a resource-pack (JSON UI) screen: the key the pack recognises in the window title and the number of
 * slots it draws. Immutable; register it with {@see Screens::register()}.
 *
 * Measured on Windows Bedrock 1.26.65: the vanilla container screen draws 27 cells, but a pack screen selected by the
 * window title is sent every slot up to the container size (54 and 243 tested).
 */
final class Screen{
    /** Title prefix of the Bedrock research wiki convention; a key normally starts with it. */
    public const PREFIX = "§b§w";

    public const MIN_SIZE = 1;
    public const MAX_SIZE = 243;

    /**
     * @param string $key         only `§x` colour-code pairs, e.g. `§b§w§r§s`
     * @param bool   $strictPrefix when true (default) the key must start with {@see PREFIX}
     *
     * @throws InvalidArgumentException
     */
    public function __construct(
        private readonly string $key,
        private readonly int $size,
        bool $strictPrefix = true,
    ){
        if(!self::isValidKey($key)){
            throw new InvalidArgumentException("Screen key must be one or more '§x' pairs, got \"$key\"");
        }
        if($strictPrefix && !str_starts_with($key, self::PREFIX)){
            throw new InvalidArgumentException("Screen key \"$key\" must start with \"" . self::PREFIX . "\" (pass strictPrefix: false to use another key)");
        }
        if($size < self::MIN_SIZE || $size > self::MAX_SIZE){
            throw new InvalidArgumentException("Screen size must be between " . self::MIN_SIZE . " and " . self::MAX_SIZE . ", got $size");
        }
    }

    public static function builder() : ScreenBuilder{
        return new ScreenBuilder();
    }

    /** Whether $key is a non-empty run of `§` + one character pairs. */
    public static function isValidKey(string $key) : bool{
        return preg_match('/^(?:§[0-9a-z])+$/u', $key) === 1;
    }

    public function getKey() : string{
        return $this->key;
    }

    public function getSize() : int{
        return $this->size;
    }

    /** A container type that opens the screen: window CONTAINER, this size, this key as the screen key. */
    public function toContainerType() : ContainerType{
        return ContainerType::builder()->size($this->size)->screenKey($this->key)->build();
    }
}
