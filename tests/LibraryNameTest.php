<?php

declare(strict_types=1);

namespace kim\present\libraryname\tests;

use kim\present\libraryname\LibraryName;
use PHPUnit\Framework\TestCase;
use pocketmine\VersionInfo;

final class LibraryNameTest extends TestCase{
    public function testGreet() : void{
        self::assertSame("Hello, Steve!", LibraryName::greet("Steve"));
    }

    public function testServerDependencyIsAutoloaded() : void{
        self::assertNotSame("", VersionInfo::BASE_VERSION);
    }
}
