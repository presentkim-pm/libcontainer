<?php

declare(strict_types=1);

namespace kim\present\libcontainer\tests;

use kim\present\libcontainer\Libcontainer;
use PHPUnit\Framework\TestCase;
use pocketmine\VersionInfo;

final class LibcontainerTest extends TestCase{
    public function testGreet() : void{
        self::assertSame("Hello, Steve!", Libcontainer::greet("Steve"));
    }

    public function testServerDependencyIsAutoloaded() : void{
        self::assertNotSame("", VersionInfo::BASE_VERSION);
    }
}
