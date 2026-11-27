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
use function array_key_exists;
use function array_map;

/**
 * Registry of {@see Screen}s by key. A key names one pack screen, so two plugins registering the same key would
 * fight over it: that is rejected.
 */
final class Screens{
    /** @var array<string, array{Screen, ContainerType}> */
    private static array $screens = [];

    private function __construct(){
    }

    /**
     * Registers the screen and returns the container type that opens it.
     *
     * @throws InvalidArgumentException if the key is already registered
     */
    public static function register(Screen $screen) : ContainerType{
        $key = $screen->getKey();
        if(array_key_exists($key, self::$screens)){
            throw new InvalidArgumentException("Screen key \"$key\" is already registered (two plugins using the same key?)");
        }
        $type = $screen->toContainerType();
        self::$screens[$key] = [$screen, $type];
        return $type;
    }

    public static function has(string $key) : bool{
        return array_key_exists($key, self::$screens);
    }

    /** @throws InvalidArgumentException if the key is not registered */
    public static function get(string $key) : Screen{
        return (self::$screens[$key] ?? throw new InvalidArgumentException("Unknown screen key \"$key\""))[0];
    }

    /** @throws InvalidArgumentException if the key is not registered */
    public static function getType(string $key) : ContainerType{
        return (self::$screens[$key] ?? throw new InvalidArgumentException("Unknown screen key \"$key\""))[1];
    }

    /** @return array<string, Screen> */
    public static function all() : array{
        return array_map(static fn(array $entry) : Screen => $entry[0], self::$screens);
    }
}
