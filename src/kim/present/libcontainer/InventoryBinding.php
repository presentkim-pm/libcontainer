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

use pocketmine\inventory\CallbackInventoryListener;
use pocketmine\inventory\Inventory;
use pocketmine\item\Item;

/**
 * Two-way live mirror between an existing inventory and a slot range of a menu.
 *
 * Every change on one side is written to the other. While a change is being forwarded the binding ignores the echo
 * the other side reports back, so there is no recursion.
 *
 * @internal
 */
final class InventoryBinding{
    private bool $forwarding = false;
    private readonly CallbackInventoryListener $menuListener;
    private readonly CallbackInventoryListener $sourceListener;
    private bool $active = true;

    public function __construct(
        private readonly Inventory $menuInventory,
        private readonly Inventory $source,
        private readonly int $offset,
    ){
        $size = $source->getSize();

        $this->menuListener = new CallbackInventoryListener(
            function(Inventory $inventory, int $slot, Item $oldItem) use ($size) : void{
                $sourceSlot = $slot - $this->offset;
                if($sourceSlot >= 0 && $sourceSlot < $size){
                    $this->forward(fn() => $this->source->setItem($sourceSlot, $this->menuInventory->getItem($slot)));
                }
            },
            function(Inventory $inventory, array $oldContents) use ($size) : void{
                $this->forward(function() use ($size) : void{
                    $contents = [];
                    for($i = 0; $i < $size; ++$i){
                        $contents[$i] = $this->menuInventory->getItem($this->offset + $i);
                    }
                    $this->source->setContents($contents);
                });
            }
        );
        $this->sourceListener = new CallbackInventoryListener(
            function(Inventory $inventory, int $slot, Item $oldItem) : void{
                $this->forward(fn() => $this->menuInventory->setItem($this->offset + $slot, $this->source->getItem($slot)));
            },
            function(Inventory $inventory, array $oldContents) use ($size) : void{
                $this->forward(function() use ($size) : void{
                    for($i = 0; $i < $size; ++$i){
                        $this->menuInventory->setItem($this->offset + $i, $this->source->getItem($i));
                    }
                });
            }
        );

        //the source is the truth when the binding starts
        $this->forward(function() use ($size) : void{
            for($i = 0; $i < $size; ++$i){
                $this->menuInventory->setItem($this->offset + $i, $this->source->getItem($i));
            }
        });

        $menuInventory->getListeners()->add($this->menuListener);
        $source->getListeners()->add($this->sourceListener);
    }

    public function getSource() : Inventory{
        return $this->source;
    }

    public function getOffset() : int{
        return $this->offset;
    }

    public function getSize() : int{
        return $this->source->getSize();
    }

    public function isActive() : bool{
        return $this->active;
    }

    /** Stops mirroring. The menu keeps the items it has now. */
    public function release() : void{
        if($this->active){
            $this->active = false;
            $this->menuInventory->getListeners()->remove($this->menuListener);
            $this->source->getListeners()->remove($this->sourceListener);
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
