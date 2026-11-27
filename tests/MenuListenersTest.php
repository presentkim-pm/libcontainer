<?php

declare(strict_types=1);

namespace kim\present\libcontainer\tests;

use kim\present\libcontainer\Menu;
use PHPUnit\Framework\TestCase;
use pocketmine\player\Player;

final class MenuListenersTest extends TestCase{
    public function testCloseListenersCanBeRemoved() : void{
        $menu = Menu::chest();
        $player = new FakePlayer();
        $calls = [];
        $stopA = $menu->listenClose(function(Player $p) use (&$calls) : void{ $calls[] = "a"; });
        $menu->listenClose(function(Player $p) use (&$calls) : void{ $calls[] = "b"; });

        $menu->handleClosed($player);
        $stopA();
        $stopA(); // safe twice
        $menu->handleClosed($player);

        self::assertSame(["a", "b", "b"], $calls);
    }

    public function testListenerMayUnsubscribeItselfWhileCalled() : void{
        $menu = Menu::chest();
        $player = new FakePlayer();
        $count = 0;
        $stop = static fn() => null;
        $stop = $menu->listenClose(function() use (&$count, &$stop) : void{ ++$count; $stop(); });
        $menu->listenClose(function() use (&$count) : void{ ++$count; });

        $menu->handleClosed($player);
        $menu->handleClosed($player);

        self::assertSame(3, $count);
    }

    public function testCloseListenersRunAfterOnCloseHandlers() : void{
        $menu = Menu::chest();
        $order = [];
        $menu->listenClose(function() use (&$order) : void{ $order[] = "listener"; });
        $menu->onClose(function() use (&$order) : void{ $order[] = "handler"; });
        $menu->handleClosed(new FakePlayer());
        self::assertSame(["handler", "listener"], $order);
    }
}
