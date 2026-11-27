<?php

declare(strict_types=1);

namespace kim\present\libcontainer\tests\logic;

use InvalidArgumentException;
use kim\present\libcontainer\logic\OpenRetry;
use kim\present\libcontainer\logic\RetryStep;
use PHPUnit\Framework\TestCase;

final class OpenRetryTest extends TestCase{
    public function testDefaults() : void{
        $retry = new OpenRetry();
        self::assertSame([5, 10, 20, 40], [$retry->delayFor(1), $retry->delayFor(2), $retry->delayFor(3), $retry->delayFor(4)]);
        self::assertNull($retry->delayFor(5));
        self::assertNull($retry->delayFor(0));
    }

    public function testShortListRepeatsLastDelay() : void{
        $retry = new OpenRetry(4, [3, 7]);
        self::assertSame([3, 7, 7, 7], [$retry->delayFor(1), $retry->delayFor(2), $retry->delayFor(3), $retry->delayFor(4)]);
        self::assertNull($retry->delayFor(5));
    }

    public function testLongListIsLimitedByAttempts() : void{
        $retry = new OpenRetry(2, [1, 2, 3]);
        self::assertSame(2, $retry->delayFor(2));
        self::assertNull($retry->delayFor(3));
    }

    public function testZeroAttemptsNeverRetries() : void{
        $retry = new OpenRetry(0, []);
        self::assertNull($retry->delayFor(1));
    }

    public function testRejectsInvalidConfig() : void{
        foreach([[-1, [5]], [2, []], [2, [5, 0]]] as [$attempts, $delays]){
            try{
                new OpenRetry($attempts, $delays);
                self::fail("expected InvalidArgumentException");
            }catch(InvalidArgumentException){
                self::assertTrue(true);
            }
        }
    }

    public function testDecide() : void{
        self::assertSame(RetryStep::OPEN, OpenRetry::decide(true, true, false));
        self::assertSame(RetryStep::GIVE_UP, OpenRetry::decide(false, true, false));
        self::assertSame(RetryStep::GIVE_UP, OpenRetry::decide(true, false, false));
        self::assertSame(RetryStep::GIVE_UP, OpenRetry::decide(true, true, true));
    }
}
