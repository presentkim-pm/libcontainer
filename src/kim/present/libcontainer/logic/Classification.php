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

namespace kim\present\libcontainer\logic;

/**
 * Result of {@see TransactionClassifier::classify()}. Every slot list is unique and in order of first appearance.
 *
 * @internal
 */
final readonly class Classification{
    /**
     * @param list<int> $buttonSlots
     * @param list<int> $lockedSlots
     * @param list<int> $freeSlots
     */
    public function __construct(
        public Outcome $outcome,
        public array $buttonSlots,
        public array $lockedSlots,
        public array $freeSlots,
    ){
    }
}
