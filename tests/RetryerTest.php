<?php

declare(strict_types=1);

namespace Retryer\Tests;

use Retryer\Retryer;
use Retryer\Exception;
use PHPUnit\Framework\TestCase;

class RetryerTest extends TestCase
{
    /**
     * Test success action executed only once
     *
     * @param integer $times How many times to retry.
     *
     * @dataProvider provideConfiguration
     *
     * @return void
     */
    public function testSuccessAction(int $times): void
    {
        $counter = 0;
        (new Retryer())
            ->do(
                function () use (&$counter): void {
                    $counter++;
                }
            )
            ->times($times)
            ->execute();
        self::assertEquals(1, $counter);
    }

    /**
     * Test failing action executed all required times
     *
     * @param integer $times How many times to retry.
     *
     * @dataProvider provideConfiguration
     *
     * @return void
     */
    public function testFailedAction(int $times): void
    {
        $counter = 0;
        (new Retryer())
            ->do(
                function () use (&$counter): void {
                    $counter++;
                    throw new \Exception();
                }
            )
            ->times($times)
            ->execute();
        self::assertEquals($times, $counter);
    }

    /**
     * Test throwing exception on last try
     *
     * @return void
     */
    public function testThrowExceptionOnLastTry(): void
    {
        $exception = new \RuntimeException('Test');
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage($exception->getMessage());
        (new Retryer())
            ->do(
                function () use ($exception): void {
                    throw $exception;
                }
            )
            ->throwExceptionOnLastTry()
            ->execute();
    }

    /**
     * Test breaking execution on specific exception
     *
     * @return void
     */
    public function testBreakingExceptions(): void
    {
        $exception = new \RuntimeException('Test');
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage($exception->getMessage());
        (new Retryer())
            ->do(
                function () use ($exception): void {
                    throw $exception;
                }
            )
            ->withBreakingExceptions([\RuntimeException::class])
            ->execute();
    }

    /**
     * Test mutually exclusive backoff policy (exponential over linear) will throw exception
     *
     * @testdox Mutually exclusive backoff policy (exponential over linear)
     *
     * @return void
     */
    public function testMutuallyExclusiveBackoffPolicyExponentialOverLinear(): void
    {
        $this->expectException(Exception\MutuallyExclusiveBackoffPolicyException::class);
        $this->expectExceptionMessage('Tried to use exponential backoff policy with linear multiplier != 1');
        (new Retryer())
            ->useLinearBackoffMultiplier(2)
            ->useExponentialBackoff();
    }

    /**
     * Test mutually exclusive backoff policy (linear over exponential) will throw exception
     *
     * @testdox Mutually exclusive backoff policy (linear over exponential)
     *
     * @return void
     */
    public function testMutuallyExclusiveBackoffPolicyLinearOverExponential(): void
    {
        $this->expectException(Exception\MutuallyExclusiveBackoffPolicyException::class);
        $this->expectExceptionMessage('Tried to use linear backoff with exponential enabled');
        (new Retryer())
            ->useExponentialBackoff()
            ->useLinearBackoffMultiplier(2);
    }

    /**
     * Test invalid retry times will throw exception
     *
     * @param integer $times How many times to retry.
     *
     * @dataProvider provideConfiguration
     *
     * @return void
     */
    public function testInvalidRetryTimes(int $times): void
    {
        $times *= -1;
        $this->expectException(Exception\InvalidRetryTimesException::class);
        $this->expectExceptionMessage("Tried to set {$times} retry times");
        (new Retryer())
            ->times($times);
    }

    /**
     * Test invalid delay will throw exception
     *
     * @param integer $delay Microseconds between iterations.
     *
     * @dataProvider provideConfiguration
     *
     * @return void
     */
    public function testInvalidDelay(int $delay): void
    {
        $delay *= -1;
        $this->expectException(Exception\InvalidDelayException::class);
        $this->expectExceptionMessage("Tried to set {$delay} as delay");
        (new Retryer())
            ->withDelay($delay);
    }

    /**
     * @return array<int[]>
     */
    public function provideConfiguration(): array
    {
        return [
            [3],
        ];
    }
}
