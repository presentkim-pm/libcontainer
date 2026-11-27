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

use kim\present\libcontainer\logic\BindRange;
use pocketmine\inventory\CallbackInventoryListener;
use pocketmine\inventory\Inventory;
use pocketmine\item\Item;

/**
 * Live mirror between a slot range of an existing inventory and a slot range of a menu.
 *
 * Every change on one side is written to the other (only source to menu when read-only). While a change is being
 * forwarded the binding ignores the echo the other side reports back, so there is no recursion. Button slots are never
 * mirrored, and while the menu is in a {@see Menu::batch()} nothing is written back to the source: the binding only
 * remembers that it must sync once the batch ends.
 *
 * @internal
 */
final class InventoryBinding{
    private bool $forwarding = false;
    private bool $dirty = false;
    private bool $active = true;
    private readonly Inventory $menuInventory;
    private readonly CallbackInventoryListener $menuListener;
    private readonly CallbackInventoryListener $sourceListener;

    public function __construct(
        private readonly Menu $menu,
        private readonly Inventory $source,
        private readonly BindRange $range,
        private readonly bool $readOnly,
    ){
        $this->menuInventory = $menu->getInventory();

        $this->menuListener = new CallbackInventoryListener(
            function(Inventory $inventory, int $slot, Item $oldItem) : void{
                if($this->forwarding || !$this->range->containsMenuSlot($slot) || $this->menu->getRules()->isButton($slot)){
                    return;
                }
                if($this->menu->isBatching()){
                    $this->dirty = true; //restored from the source when the batch ends
                }elseif(!$this->readOnly){
                    $this->forward(fn() => $this->source->setItem($this->range->toSource($slot), $this->menuInventory->getItem($slot)));
                }
            },
            function(Inventory $inventory, array $oldContents) : void{
                if($this->forwarding){
                    return;
                }
                if($this->menu->isBatching()){
                    $this->dirty = true;
                }elseif(!$this->readOnly){
                    $this->forward(function() : void{
                        for($slot = $this->range->offset; $slot < $this->range->menuEnd(); ++$slot){
                            if(!$this->menu->getRules()->isButton($slot)){
                                $this->source->setItem($this->range->toSource($slot), $this->menuInventory->getItem($slot));
                            }
                        }
                    });
                }
            }
        );
        $this->sourceListener = new CallbackInventoryListener(
            function(Inventory $inventory, int $slot, Item $oldItem) : void{
                if($this->forwarding || !$this->range->containsSourceSlot($slot)){
                    return;
                }
                if($this->menu->isBatching()){
                    $this->dirty = true;
                }else{
                    $this->syncSlot($this->range->toMenu($slot));
                }
            },
            function(Inventory $inventory, array $oldContents) : void{
                if($this->forwarding){
                    return;
                }
                if($this->menu->isBatching()){
                    $this->dirty = true;
                }else{
                    $this->syncToMenu();
                }
            }
        );

        //the source is the truth when the binding starts
        $this->syncToMenu();

        $this->menuInventory->getListeners()->add($this->menuListener);
        $source->getListeners()->add($this->sourceListener);
    }

    public function getSource() : Inventory{
        return $this->source;
    }

    public function getRange() : BindRange{
        return $this->range;
    }

    public function getOffset() : int{
        return $this->range->offset;
    }

    public function getSize() : int{
        return $this->range->count;
    }

    public function isReadOnly() : bool{
        return $this->readOnly;
    }

    public function isActive() : bool{
        return $this->active;
    }

    public function coversMenuSlot(int $slot) : bool{
        return $this->range->containsMenuSlot($slot);
    }

    /** Shows the source's item in the menu slot again (or does so when the batch ends) */
    public function refreshSlot(int $menuSlot) : void{
        if(!$this->active || !$this->range->containsMenuSlot($menuSlot)){
            return;
        }
        if($this->menu->isBatching()){
            $this->dirty = true;
        }else{
            $this->syncSlot($menuSlot);
        }
    }

    /** Called by the menu when its outermost batch ends */
    public function flush() : void{
        if($this->active && $this->dirty){
            $this->dirty = false;
            $this->syncToMenu();
        }
    }

    /** Stops mirroring. The menu keeps the items it has now. */
    public function release() : void{
        if($this->active){
            $this->active = false;
            $this->menuInventory->getListeners()->remove($this->menuListener);
            $this->source->getListeners()->remove($this->sourceListener);
        }
    }

    private function syncToMenu() : void{
        $this->forward(function() : void{
            for($slot = $this->range->offset; $slot < $this->range->menuEnd(); ++$slot){
                $this->copyToMenu($slot);
            }
        });
    }

    private function syncSlot(int $menuSlot) : void{
        $this->forward(fn() => $this->copyToMenu($menuSlot));
    }

    private function copyToMenu(int $menuSlot) : void{
        if($this->menu->getRules()->isButton($menuSlot)){
            return;
        }
        $item = $this->source->getItem($this->range->toSource($menuSlot));
        if(!$this->menuInventory->getItem($menuSlot)->equalsExact($item)){
            $this->menuInventory->setItem($menuSlot, $item);
        }
    }

    private function forward(\Closure $apply) : void{
        if($this->forwarding){
            return;
        }
        $this->forwarding = true;
        try{
            $apply();
        }finally{
            $this->forwarding = false;
        }
    }
}
