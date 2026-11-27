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

use function implode;
use function preg_match;
use function round;

/** Text rules for labels that a JSON UI label control draws. Pure, no server needed. */
final class Text{
    /** Colour code that resets formatting, put in front of text a label would not draw */
    public const RESET = "§r";

    private function __construct(){
    }

    /**
     * Measured: a JSON UI label does not draw text that consists only of a number. A name made of digits (and the
     * separators of a number, or a percent sign) gets {@see RESET} in front, which the label does not draw either.
     */
    public static function label(string $text) : string{
        return preg_match('/^[0-9.,+\-%\s]+$/', $text) === 1 ? self::RESET . $text : $text;
    }

    /**
     * The text a pack reads with `#hover_text`: the name, then every lore line, each on its own line.
     *
     * @param list<string> $lore
     */
    public static function hover(string $name, array $lore) : string{
        return self::label($name) . ($lore === [] ? "" : "\n" . implode("\n", $lore));
    }

    /**
     * Durability to show as $current of $total, as the damage of an item that can take $max damage.
     *
     * The client reads `#item_durability_total_amount` from the item type, so the total is always $max; $current is
     * scaled to it ($current / $total of $max, rounded).
     *
     * @throws \InvalidArgumentException
     */
    public static function damage(int $current, int $total, int $max) : int{
        if($total < 1 || $current < 0 || $current > $total || $max < 1){
            throw new \InvalidArgumentException("Durability $current of $total (item max $max) is not valid");
        }
        return $max - (int) round($current / $total * $max);
    }
}
