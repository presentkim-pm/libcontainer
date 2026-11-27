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

use InvalidArgumentException;
use function array_values;
use function count;
use function min;

/**
 * Retry schedule for a window the client rejected (it sends a close with window id 255 while its chat or pause screen
 * is still open). Pure, no server needed.
 *
 * Attempt N (1-based) waits {@see delayFor()} ticks after the Nth rejection. When fewer delays than attempts are
 * given, the last delay repeats.
 */
final class OpenRetry{
    public const DEFAULT_ATTEMPTS = 4;
    /** @var list<int> */
    public const DEFAULT_DELAYS = [5, 10, 20, 40];

    /** @var list<int> */
    private array $delays;

    /**
     * @param list<int> $delays ticks, each at least 1
     */
    public function __construct(private readonly int $attempts = self::DEFAULT_ATTEMPTS, array $delays = self::DEFAULT_DELAYS){
        if($attempts < 0){
            throw new InvalidArgumentException("attempts must not be negative");
        }
        if($attempts > 0 && $delays === []){
            throw new InvalidArgumentException("at least one delay is required");
        }
        foreach($delays as $delay){
            if($delay < 1){
                throw new InvalidArgumentException("delays must be at least 1 tick");
            }
        }
        $this->delays = array_values($delays);
    }

    public function getAttempts() : int{
        return $this->attempts;
    }

    /** @return list<int> */
    public function getDelays() : array{
        return $this->delays;
    }

    /**
     * Ticks to wait before retry number $attempt (1-based), or null when no retry is left.
     */
    public function delayFor(int $attempt) : ?int{
        if($attempt < 1 || $attempt > $this->attempts){
            return null;
        }
        return $this->delays[min($attempt, count($this->delays)) - 1];
    }

    /**
     * What a scheduled retry does once it is due.
     *
     * @param bool $online the player is still connected
     * @param bool $stillWanted the menu was not closed by code and not replaced by another open of this library
     * @param bool $otherWindowOpen the player has another window open now
     */
    public static function decide(bool $online, bool $stillWanted, bool $otherWindowOpen) : RetryStep{
        return $online && $stillWanted && !$otherWindowOpen ? RetryStep::OPEN : RetryStep::GIVE_UP;
    }
}
