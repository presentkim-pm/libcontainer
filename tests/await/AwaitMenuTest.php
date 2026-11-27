<?php

declare(strict_types=1);

namespace kim\present\libcontainer\tests\await;

use kim\present\libcontainer\await\AwaitMenu;
use kim\present\libcontainer\Menu;
use PHPUnit\Framework\TestCase;
use kim\present\libcontainer\tests\FakePlayer;
use pocketmine\player\Player;
use SOFe\AwaitGenerator\Await;

final class AwaitMenuTest extends TestCase{
    public function testNextClickAndChooseResolveNullOnClose() : void{
        $menu = Menu::chest();
        $player = new FakePlayer();
        $results = [];
        Await::g2c(AwaitMenu::choose($menu, $player), function($slot) use (&$results) : void{ $results[] = $slot; });
        Await::g2c(AwaitMenu::nextClick($menu, $player), function($click) use (&$results) : void{ $results[] = $click; });
        self::assertSame([], $results);

        $menu->handleClosed($player);

        self::assertSame([null, null], $results);
    }

    public function testOtherPlayersDoNotResolve() : void{
        $menu = Menu::chest();
        $player = new FakePlayer();
        $other = new FakePlayer();
        $resolved = false;
        Await::g2c(AwaitMenu::closed($menu, $player), function() use (&$resolved) : void{ $resolved = true; });

        $menu->handleClosed($other);
        self::assertFalse($resolved);

        $menu->handleClosed($player);
        self::assertTrue($resolved);
    }

    public function testListenersAreRemovedAfterResolving() : void{
        $menu = Menu::chest();
        $player = new FakePlayer();
        $count = 0;
        Await::g2c(AwaitMenu::closed($menu, $player), function() use (&$count) : void{ ++$count; });

        $menu->handleClosed($player);
        $menu->handleClosed($player); // would resume a finished generator if the listener were still there

        self::assertSame(1, $count);
    }

    public function testNextClickRemovesBothListeners() : void{
        $menu = Menu::chest();
        $player = new FakePlayer();
        $count = 0;
        Await::g2c(AwaitMenu::nextClick($menu, $player), function() use (&$count) : void{ ++$count; });
        $menu->handleClosed($player);
        $menu->handleClosed($player);
        self::assertSame(1, $count);
    }
}
