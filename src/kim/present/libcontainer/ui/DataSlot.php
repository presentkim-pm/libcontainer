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
use kim\present\libcontainer\logic\Text;
use pocketmine\item\Item;
use pocketmine\item\VanillaItems;
use function count;

/**
 * Text and numbers for a locked slot that a pack screen reads instead of drawing an item: the value object behind
 * {@see ScreenMenu::data()}.
 *
 * The values travel in an ordinary item, which is what the client sends to JSON UI bindings:
 * - name and lore: `#hover_text` (the name, then the lore lines). A numeric-only name gets a `§r` prefix because
 *   a label does not draw such text.
 * - count: `#inventory_stack_count`, 1 to {@see MAX_COUNT}.
 * - durability: `#item_durability_current_amount` and `#item_durability_total_amount`. The item is a
 *   {@see DURABILITY_ITEM} (a Durable item), whose total is always its own maximum durability, so
 *   the damage is scaled: current / total of that maximum. A pack that needs the plain ratio uses the two bindings.
 *   A durable item cannot be stacked, so count and durability exclude each other.
 */
final readonly class DataSlot{
    public const MAX_COUNT = 64;

    /** Name of the item that carries the durability (a diamond pickaxe, 1561 uses). Fixed, so packs can rely on it. */
    public const DURABILITY_ITEM = "DIAMOND_PICKAXE";
    /** Name of the item that carries everything else (paper). */
    public const PLAIN_ITEM = "PAPER";

    /**
     * @param list<string>             $lore
     * @param array{int, int}|null     $durability [current, total], 0 <= current <= total, total >= 1
     *
     * @throws InvalidArgumentException
     */
    public function __construct(
        public string $name,
        public array $lore = [],
        public ?int $count = null,
        public ?array $durability = null,
    ){
        if($count !== null && ($count < 1 || $count > self::MAX_COUNT)){
            throw new InvalidArgumentException("Count must be between 1 and " . self::MAX_COUNT . ", got $count");
        }
        if($durability !== null){
            if(count($durability) !== 2 || $durability[1] < 1 || $durability[0] < 0 || $durability[0] > $durability[1]){
                throw new InvalidArgumentException("Durability must be [current, total] with 0 <= current <= total and total >= 1");
            }
            if($count !== null){
                throw new InvalidArgumentException("A durable item cannot be stacked: use either count or durability");
            }
        }
    }

    /** What `#hover_text` will contain. */
    public function getHoverText() : string{
        return Text::hover($this->name, $this->lore);
    }

    /** Builds the item for the slot; needs the server's item registry. */
    public function toItem() : Item{
        if($this->durability !== null){
            $item = VanillaItems::DIAMOND_PICKAXE();
            [$current, $total] = $this->durability;
            $item->setDamage(Text::damage($current, $total, $item->getMaxDurability()));
        }else{
            $item = VanillaItems::PAPER();
            if($this->count !== null){
                $item->setCount($this->count);
            }
        }
        return $item->setCustomName(Text::label($this->name))->setLore($this->lore);
    }
}
