<?php

declare(strict_types=1);

namespace Internal;

use Spiral\Grpc\Client\Internal\BackoffThrottler;
use Testo\Assert;
use Testo\Data\DataProvider;
use Testo\Expect;
use Testo\Test;

#[Test]
final class BackoffThrottlerTest
{
    public static function provideConstructorInvalidArguments(): iterable
    {
        yield 'maxInterval is 0' => [0, 0.1, 2.0];
        yield 'maxJitterCoefficient is negative' => [1, -0.1, 2.0];
        yield 'maxJitterCoefficient is 1' => [1, 1.0, 2.0];
        yield 'backoffCoefficient is less than 1' => [1, 0.1, 0.9];
    }

    public static function provideCalculatorData(): iterable
    {
        yield 'first attempt' => [1000, 1, 1000];
        yield 'second attempt' => [1500, 2, 500];
        yield 'third attempt' => [4500, 3, 500];
        yield 'overflow' => [300_000, 100, 500];
    }

    public static function provideCalculatorInvalidArgs(): iterable
    {
        yield 'fails is negative' => [-1, 100];
        yield 'fails is zero' => [0, 100];
        yield 'interval is negative' => [1, -100];
        yield 'interval is zero' => [1, 0];
    }

    #[DataProvider('provideConstructorInvalidArguments')]
    public function invalidArguments(
        int $maxInterval,
        float $maxJitterCoefficient,
        float $backoffCoefficient,
    ): void {
        Expect::exception(\InvalidArgumentException::class);

        new BackoffThrottler($maxInterval, $maxJitterCoefficient, $backoffCoefficient);
    }

    #[DataProvider('provideCalculatorData')]
    public function calculateSleepTime(int $expected, int $fails, int $interval): void
    {
        $throttler = new BackoffThrottler(300_000, 0.0, 3.0);

        Assert::same($throttler->calculateSleepTime($fails, $interval), $expected);
    }

    #[DataProvider('provideCalculatorInvalidArgs')]
    public function calculateSleepTimeInvalidArgs(int $fails, int $interval): void
    {
        $throttler = new BackoffThrottler(300_000, 0.0, 3.0);

        Expect::exception(\InvalidArgumentException::class);
        $throttler->calculateSleepTime($fails, $interval);
    }

    public function calculateSleepTimeWithJitter(): void
    {
        $throttler = new BackoffThrottler(300_000, 0.2, 2.0);

        $sleepTime = $throttler->calculateSleepTime(1, 1000);
        $notSame = false;

        for ($i = 20; --$i;) {
            $sleep = $throttler->calculateSleepTime(1, 1000);
            $notSame = $notSame || $sleep !== $sleepTime;

            Assert::int($sleep)->greaterThanOrEqual(800)->lessThanOrEqual(1200);
        }

        Assert::true($notSame);
    }
}
