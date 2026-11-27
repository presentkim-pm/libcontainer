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

namespace kim\present\libcontainer\display;

use kim\present\libcontainer\Menu;
use pocketmine\network\mcpe\protocol\ClientboundPacket;
use pocketmine\player\Player;

/**
 * Turns a server-side menu window into what the client has to be told to show it.
 *
 * Implementations RETURN the packets instead of sending them: for opening, the server's InventoryManager sends them
 * itself, in order, at the moment the window may open (it waits for the close acknowledgement of the previous
 * window), and libcontainer sends the packets of {@see hide()} when the window is closed.
 */
interface ContainerDisplay{
    /**
     * Packets that open the window. They must include a ContainerOpenPacket carrying $windowId.
     *
     * @return list<ClientboundPacket>
     */
    public function show(Player $player, Menu $menu, int $windowId) : array;

    /**
     * Packets that clean up after the window was closed (may be empty).
     *
     * @return list<ClientboundPacket>
     */
    public function hide(Player $player, Menu $menu) : array;
}
