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
use kim\present\libcontainer\logic\Pagination;
use pocketmine\item\Item;
use pocketmine\player\Player;
use function array_unique;
use function array_values;
use function count;
use function in_array;

/**
 * A {@see Menu} that shows a long list of {@see PageEntry}s a page at a time, with previous / next buttons.
 *
 * Content slots are filled in order; entries with a callback are buttons, the others are locked items. Buttons that
 * lead nowhere (no previous page on the first page) are cleared. Page maths lives in {@see Pagination}.
 */
final class PagedMenu{
    /** @var list<int> */
    private readonly array $contentSlots;
    /** @var list<PageEntry> */
    private array $entries = [];
    private int $page = 0;
    private ?int $previousSlot = null;
    private ?Item $previousItem = null;
    private ?int $nextSlot = null;
    private ?Item $nextItem = null;
    private ?int $indicatorSlot = null;
    /** @var (\Closure(int, int) : Item)|null */
    private ?\Closure $indicator = null;

    /**
     * @param list<int> $contentSlots slots that show entries, in fill order
     *
     * @throws InvalidArgumentException if the list is empty, has duplicates or a slot outside the menu
     */
    public function __construct(private readonly Menu $menu, array $contentSlots){
        $contentSlots = array_values($contentSlots);
        if($contentSlots === []){
            throw new InvalidArgumentException("A paged menu needs at least one content slot");
        }
        if(count(array_unique($contentSlots)) !== count($contentSlots)){
            throw new InvalidArgumentException("Content slots must be unique");
        }
        foreach($contentSlots as $slot){
            $this->assertSlot($slot);
        }
        $this->contentSlots = $contentSlots;
    }

    /**
     * Where the previous / next buttons go and how they look. Cloned for every use.
     *
     * @throws InvalidArgumentException if a slot is outside the menu or used twice
     */
    public function setNavigation(int $previousSlot, Item $previousItem, int $nextSlot, Item $nextItem) : self{
        $this->assertSlot($previousSlot);
        $this->assertSlot($nextSlot);
        if($previousSlot === $nextSlot){
            throw new InvalidArgumentException("Previous and next need different slots");
        }
        $this->assertNotContent($previousSlot);
        $this->assertNotContent($nextSlot);
        $this->clearSlot($this->previousSlot);
        $this->clearSlot($this->nextSlot);
        [$this->previousSlot, $this->previousItem, $this->nextSlot, $this->nextItem] = [$previousSlot, clone $previousItem, $nextSlot, clone $nextItem];
        $this->render();
        return $this;
    }

    /**
     * Optional page indicator: a locked item at $slot made by $factory(page, pageCount), both counted from 1.
     *
     * @param \Closure(int, int) : Item $factory
     */
    public function setIndicator(int $slot, \Closure $factory) : self{
        $this->assertSlot($slot);
        $this->assertNotContent($slot);
        $this->clearSlot($this->indicatorSlot);
        $this->indicatorSlot = $slot;
        $this->indicator = $factory;
        $this->render();
        return $this;
    }

    /**
     * Replaces the entries and shows the page again (the page is clamped to the new page count).
     *
     * @param list<PageEntry> $entries
     */
    public function setEntries(array $entries) : self{
        $this->entries = array_values($entries);
        $this->page = Pagination::clamp($this->page, count($this->entries), $this->getPerPage());
        $this->render();
        return $this;
    }

    /** @return list<PageEntry> */
    public function getEntries() : array{
        return $this->entries;
    }

    public function getMenu() : Menu{
        return $this->menu;
    }

    public function getPerPage() : int{
        return count($this->contentSlots);
    }

    /** Current page, counted from 0. */
    public function getPage() : int{
        return $this->page;
    }

    public function getPageCount() : int{
        return Pagination::pageCount(count($this->entries), $this->getPerPage());
    }

    /** Shows page $page, counted from 0; out of range values are clamped. */
    public function setPage(int $page) : self{
        $this->page = Pagination::clamp($page, count($this->entries), $this->getPerPage());
        $this->render();
        return $this;
    }

    public function open(Player $player) : bool{
        $this->render();
        return $this->menu->open($player);
    }

    /** Draws the current page. Called by the setters; call it yourself only after changing the menu behind its back. */
    public function render() : void{
        [$first, $count] = Pagination::window($this->page, count($this->entries), $this->getPerPage());
        foreach($this->contentSlots as $i => $slot){
            $this->clearSlot($slot);
            if($i >= $count){
                continue;
            }
            $entry = $this->entries[$first + $i];
            if($entry->onClick !== null){
                $this->menu->setButton($slot, clone $entry->item, $entry->onClick);
            }else{
                $this->menu->lock($slot)->setItem($slot, clone $entry->item);
            }
        }
        if($this->previousSlot !== null && $this->previousItem !== null && $this->nextSlot !== null && $this->nextItem !== null){
            $this->clearSlot($this->previousSlot);
            $this->clearSlot($this->nextSlot);
            if(Pagination::hasPrevious($this->page)){
                $this->menu->setButton($this->previousSlot, clone $this->previousItem, fn() => $this->setPage($this->page - 1));
            }
            if(Pagination::hasNext($this->page, count($this->entries), $this->getPerPage())){
                $this->menu->setButton($this->nextSlot, clone $this->nextItem, fn() => $this->setPage($this->page + 1));
            }
        }
        if($this->indicatorSlot !== null && $this->indicator !== null){
            $this->menu->removeButton($this->indicatorSlot)->lock($this->indicatorSlot)
                ->setItem($this->indicatorSlot, ($this->indicator)($this->page + 1, $this->getPageCount()));
        }
    }

    private function clearSlot(?int $slot) : void{
        if($slot !== null){
            $this->menu->removeButton($slot)->lock($slot)->clear($slot);
        }
    }

    private function assertSlot(int $slot) : void{
        $size = $this->menu->getType()->getSize();
        if($slot < 0 || $slot >= $size){
            throw new InvalidArgumentException("Slot $slot is outside the menu (0.." . ($size - 1) . ")");
        }
    }

    private function assertNotContent(int $slot) : void{
        if(in_array($slot, $this->contentSlots, true)){
            throw new InvalidArgumentException("Slot $slot is already a content slot");
        }
    }
}
