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
use kim\present\libcontainer\Click;
use kim\present\libcontainer\logic\Text;
use kim\present\libcontainer\Menu;
use pocketmine\item\Item;
use pocketmine\item\VanillaItems;
use pocketmine\player\Player;

/**
 * A {@see Menu} for a registered {@see Screen}: buttons and read-only data slots for a resource-pack screen.
 *
 * It wraps a plain menu ({@see getMenu()}), so everything else (titles, handlers, bindings) works as usual. Without
 * the matching pack the client shows the vanilla 27-cell screen, which draws items, not the data slots.
 */
final class ScreenMenu{
    private function __construct(
        private readonly Menu $menu,
        private readonly Screen $screen,
    ){
    }

    /**
     * @param Screen|string $screen a registered screen or its key
     *
     * @throws InvalidArgumentException if the screen is not registered
     */
    public static function create(Screen|string $screen, string $title = "") : self{
        $key = $screen instanceof Screen ? $screen->getKey() : $screen;
        return new self(Menu::create(Screens::getType($key), $title), Screens::get($key));
    }

    public function getMenu() : Menu{
        return $this->menu;
    }

    public function getScreen() : Screen{
        return $this->screen;
    }

    /**
     * A clickable cell. The pack maps its button to `button.container_auto_place`, so a tap arrives as a quick move
     * ({@see \kim\present\libcontainer\ClickType::QUICK_MOVE}) from this slot to the player inventory, and is
     * cancelled like any button click. $label is the item name (a numeric-only label gets a `§r` prefix).
     *
     * @param \Closure(Click) : void $onClick
     * @param Item|null              $icon    the item to send, default a paper; it is cloned
     */
    public function button(int $slot, string $label, \Closure $onClick, ?Item $icon = null) : self{
        $this->assertSlot($slot);
        $item = clone ($icon ?? VanillaItems::PAPER());
        $this->menu->setButton($slot, $item->setCustomName(Text::label($label)), $onClick);
        return $this;
    }

    /**
     * A locked cell that only shows text or numbers; see {@see DataSlot} for how they reach the pack.
     *
     * @param list<string>         $lore
     * @param array{int, int}|null $durability [current, total]
     */
    public function data(int $slot, string $name, array $lore = [], ?int $count = null, ?array $durability = null) : self{
        return $this->setData($slot, new DataSlot($name, $lore, $count, $durability));
    }

    /** Puts or replaces a data cell, live for every viewer. The slot is locked and stops being a button. */
    public function setData(int $slot, DataSlot $data) : self{
        $this->assertSlot($slot);
        $this->menu->removeButton($slot)->lock($slot)->setItem($slot, $data->toItem());
        return $this;
    }

    public function open(Player $player) : bool{
        return $this->menu->open($player);
    }

    public function close(Player $player) : void{
        $this->menu->close($player);
    }

    private function assertSlot(int $slot) : void{
        if($slot < 0 || $slot >= $this->screen->getSize()){
            throw new InvalidArgumentException("Slot $slot is outside screen \"{$this->screen->getKey()}\" (0.." . ($this->screen->getSize() - 1) . ")");
        }
    }
}
