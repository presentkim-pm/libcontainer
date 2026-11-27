<?php

declare(strict_types=1);

namespace kim\present\libcontainer\tests;

use pocketmine\player\Player;

/**
 * A player that was never constructed, so it can be handed to code that only compares or passes it along.
 * The destructor of the real class closes an entity that does not exist.
 */
final class FakePlayer extends Player{
    public function __construct(){
    }

    public function __destruct(){
    }
}
