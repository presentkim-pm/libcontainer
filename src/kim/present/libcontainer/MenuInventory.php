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

use pocketmine\inventory\SimpleInventory;
use pocketmine\player\Player;

/**
 * The inventory behind a {@see Menu}. It only exists so the server's container-open callback and the transaction
 * listener can recognise a menu window and find its {@see Menu}.
 *
 * @internal
 */
final class MenuInventory extends SimpleInventory{
    public function __construct(private readonly Menu $menu, int $size){
        parent::__construct($size);
    }

    public function getMenu() : Menu{
        return $this->menu;
    }

    public function onOpen(Player $who) : void{
        parent::onOpen($who);
        $this->menu->handleOpened($who);
    }
}
