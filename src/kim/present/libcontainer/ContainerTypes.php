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
use function array_key_exists;

/** Registry of named {@see ContainerType}s, so menus can be created by id. */
final class ContainerTypes{
    public const CHEST = "libcontainer:chest";
    public const LARGE = "libcontainer:large";
    public const FULL = "libcontainer:full";

    /** @var array<string, ContainerType>|null */
    private static ?array $types = null;

    private function __construct(){
    }

    /** @throws InvalidArgumentException if the id is empty or already registered */
    public static function register(string $id, ContainerType $type) : void{
        $types = self::all();
        if($id === ""){
            throw new InvalidArgumentException("Container type id must not be empty");
        }
        if(array_key_exists($id, $types)){
            throw new InvalidArgumentException("Container type \"$id\" is already registered");
        }
        self::$types[$id] = $type;
    }

    public static function has(string $id) : bool{
        return array_key_exists($id, self::all());
    }

    /** @throws InvalidArgumentException if the id is unknown */
    public static function get(string $id) : ContainerType{
        return self::all()[$id] ?? throw new InvalidArgumentException("Unknown container type \"$id\"");
    }

    /** @return array<string, ContainerType> */
    public static function all() : array{
        return self::$types ??= [
            self::CHEST => ContainerType::builder()->size(27)->build(),
            self::LARGE => ContainerType::builder()->size(54)->build(),
            self::FULL => ContainerType::builder()->size(243)->build(),
        ];
    }
}
