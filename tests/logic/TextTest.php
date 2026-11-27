<?php

declare(strict_types=1);

namespace kim\present\libcontainer\tests\logic;

use InvalidArgumentException;
use kim\present\libcontainer\logic\Text;
use PHPUnit\Framework\TestCase;

final class TextTest extends TestCase{
    public function testNumericLabelsGetReset() : void{
        self::assertSame("§r12", Text::label("12"));
        self::assertSame("§r1,234.5", Text::label("1,234.5"));
        self::assertSame("§r-5%", Text::label("-5%"));
        self::assertSame("Gold", Text::label("Gold"));
        self::assertSame("12 coins", Text::label("12 coins"));
        self::assertSame("§a12", Text::label("§a12"));
        self::assertSame("", Text::label(""));
    }

    public function testHover() : void{
        self::assertSame("Gold", Text::hover("Gold", []));
        self::assertSame("§r100\nline 1\nline 2", Text::hover("100", ["line 1", "line 2"]));
    }

    public function testDamageScalesToTheItemMaximum() : void{
        self::assertSame(0, Text::damage(10, 10, 1561));
        self::assertSame(1561, Text::damage(0, 10, 1561));
        self::assertSame(780, Text::damage(5, 10, 1561));
        self::assertSame(1000 - 250, Text::damage(25, 100, 1000));
    }

    public function testDamageRejectsBadValues() : void{
        $this->expectException(InvalidArgumentException::class);
        Text::damage(11, 10, 100);
    }
}
