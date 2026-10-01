<?php

declare(strict_types=1);

namespace Spiral\Grpc\Client\Tests\Unit\Interceptor\RetryInterceptor;

use Spiral\Grpc\Client\Interceptor\RetryInterceptor;
use Spiral\Grpc\Client\Interceptor\RetryInterceptor\RetryOptions;
use Testo\Assert;
use Testo\Codecov\Covers;
use Testo\Core\Exception\SkipTest;
use Testo\Data\DataProvider;
use Testo\Expect;
use Testo\Test;

#[Test]
#[Covers(RetryOptions::class)]
final class RetryOptionsTest
{
    public static function provideValidValues(): iterable
    {
        yield 'initialInterval null' => ['withInitialInterval', 'initialInterval', null, null];
        yield 'initialInterval zero' => ['withInitialInterval', 'initialInterval', 0, 0];
        yield 'initialInterval positive' => ['withInitialInterval', 'initialInterval', 500, 500];
        yield 'congestionInitialInterval null' => [
            'withCongestionInitialInterval', 'congestionInitialInterval', null, null,
        ];
        yield 'congestionInitialInterval zero' => [
            'withCongestionInitialInterval', 'congestionInitialInterval', 0, 0,
        ];
        yield 'congestionInitialInterval positive' => [
            'withCongestionInitialInterval', 'congestionInitialInterval', 500, 500,
        ];
        yield 'backoffCoefficient one' => ['withBackoffCoefficient', 'backoffCoefficient', 1.0, 1.0];
        yield 'backoffCoefficient above one' => ['withBackoffCoefficient', 'backoffCoefficient', 3.5, 3.5];
        yield 'maximumInterval null' => ['withMaximumInterval', 'maximumInterval', null, null];
        yield 'maximumInterval zero' => ['withMaximumInterval', 'maximumInterval', 0, 0];
        yield 'maximumInterval positive' => ['withMaximumInterval', 'maximumInterval', 500, 500];
        yield 'maximumAttempts zero' => ['withMaximumAttempts', 'maximumAttempts', 0, 0];
        yield 'maximumAttempts positive' => ['withMaximumAttempts', 'maximumAttempts', 5, 5];
        yield 'maximumJitterCoefficient null falls back to default' => [
            'withMaximumJitterCoefficient', 'maximumJitterCoefficient', null, 0.1,
        ];
        yield 'maximumJitterCoefficient zero' => [
            'withMaximumJitterCoefficient', 'maximumJitterCoefficient', 0.0, 0.0,
        ];
        yield 'maximumJitterCoefficient inside range' => [
            'withMaximumJitterCoefficient', 'maximumJitterCoefficient', 0.5, 0.5,
        ];
    }

    public static function provideInvalidValues(): iterable
    {
        yield 'initialInterval negative' => ['withInitialInterval', -1];
        yield 'congestionInitialInterval negative' => ['withCongestionInitialInterval', -1];
        yield 'maximumJitterCoefficient negative' => ['withMaximumJitterCoefficient', -0.1];
        yield 'maximumJitterCoefficient one' => ['withMaximumJitterCoefficient', 1.0];
    }

    public static function provideAssertViolatingValues(): iterable
    {
        yield 'backoffCoefficient below one' => ['withBackoffCoefficient', 0.5];
        yield 'maximumInterval negative' => ['withMaximumInterval', -1];
        yield 'maximumAttempts negative' => ['withMaximumAttempts', -1];
    }

    public function toAutowirePassesItselfToInterceptor(): void
    {
        $options = new RetryOptions();

        $autowire = $options->toAutowire();

        Assert::same($autowire->alias, RetryInterceptor::class);
        Assert::same($autowire->parameters, [$options]);
    }

    /**
     * @param non-empty-string $method
     * @param non-empty-string $property
     */
    #[DataProvider('provideValidValues')]
    public function withValueReturnsModifiedCopy(string $method, string $property, mixed $value, mixed $expected): void
    {
        $options = new RetryOptions();
        $before = $options->{$property};

        $modified = $options->{$method}($value);

        Assert::notSame($modified, $options);
        Assert::same($modified->{$property}, $expected);
        Assert::same($options->{$property}, $before);
    }

    /**
     * @param non-empty-string $method
     */
    #[DataProvider('provideInvalidValues')]
    public function withInvalidValueFails(string $method, mixed $value): never
    {
        Expect::exception(\InvalidArgumentException::class);

        (new RetryOptions())->{$method}($value);
    }

    /**
     * @param non-empty-string $method
     */
    #[DataProvider('provideAssertViolatingValues')]
    public function withAssertViolatingValueFails(string $method, mixed $value): never
    {
        \ini_get('zend.assertions') === '1' or throw new SkipTest('zend.assertions is disabled');
        Expect::exception(\AssertionError::class);

        (new RetryOptions())->{$method}($value);
    }
}
