<?php

declare(strict_types=1);

namespace Spiral\Grpc\Client\Tests\Unit\Interceptor;

use Google\Protobuf\GPBEmpty;
use Google\Protobuf\StringValue;
use Spiral\Grpc\Client\Config\ConnectionConfig;
use Spiral\Grpc\Client\Config\ServiceConfig;
use Spiral\Grpc\Client\Interceptor\Helper;
use Spiral\Grpc\Client\Internal\Connection\ClientStub;
use Spiral\Grpc\Client\Internal\Connection\ConnectionInterface;
use Spiral\Grpc\Client\Internal\ServiceClient\ServiceConfigMap;
use Spiral\Interceptors\Context\CallContext;
use Spiral\Interceptors\Context\Target;
use Testo\Assert;
use Testo\Codecov\Covers;
use Testo\Data\DataProvider;
use Testo\Test;

#[Test]
#[Covers(Helper::class)]
final class HelperTest
{
    public static function provideAccessors(): iterable
    {
        yield 'connection' => ['getCurrentConnection', 'withCurrentConnection', 0, self::createConnection()];
        yield 'message' => ['getMessage', 'withMessage', 2, new StringValue(['value' => 'foo'])];
        yield 'metadata' => ['getMetadata', 'withMetadata', 4, ['auth-token' => ['secret'], 'x-trace' => ['a', 'b']]];
        yield 'options' => ['getOptions', 'withOptions', 5, ['timeout' => 1000, 'retries' => 3]];
    }

    /**
     * @param non-empty-string $getter
     * @param int<0, 5> $index
     */
    #[DataProvider('provideAccessors')]
    public function getterReadsArgumentAtExpectedIndex(string $getter, string $setter, int $index, mixed $value): void
    {
        $context = self::createContext();

        $result = Helper::{$getter}($context);

        Assert::same($result, $context->getArguments()[$index]);
    }

    /**
     * @param non-empty-string $getter
     * @param non-empty-string $setter
     * @param int<0, 5> $index
     */
    #[DataProvider('provideAccessors')]
    public function setterReplacesOnlyItsArgument(string $getter, string $setter, int $index, mixed $value): void
    {
        $context = self::createContext();
        $expected = $context->getArguments();
        $expected[$index] = $value;

        $modified = Helper::{$setter}($context, $value);

        Assert::same(Helper::{$getter}($modified), $value);
        Assert::same($modified->getArguments(), $expected);
        Assert::notSame($context->getArguments()[$index], $value);
    }

    public function getReturnTypeReadsDeserializerClass(): void
    {
        $context = self::createContext();

        Assert::same(Helper::getReturnType($context), GPBEmpty::class);
    }

    public function withReturnTypeReplacesDeserializerClass(): void
    {
        $context = self::createContext();

        $modified = Helper::withReturnType($context, StringValue::class);

        Assert::same(Helper::getReturnType($modified), StringValue::class);
        Assert::same($modified->getArguments()[3], [StringValue::class, 'decode']);
    }

    public function connectionsAreStoredInAttribute(): void
    {
        $connections = [self::createConnection(), self::createConnection()];

        $context = Helper::withConnections(self::createContext(), $connections);

        Assert::same(Helper::getConnections($context), $connections);
    }

    public function connectionsAreEmptyByDefault(): void
    {
        Assert::same(Helper::getConnections(self::createContext()), []);
    }

    public function serviceConfigIsTheOneOfTheCurrentConnection(): void
    {
        $context = self::createContext();
        $current = Helper::getCurrentConnection($context);
        $service = self::createService();

        $modified = Helper::withServiceConfigs($context, self::serviceConfigs(
            [self::createConnection(), self::createService()],
            [$current, $service],
        ));

        Assert::same(Helper::getServiceConfig($modified), $service);
    }

    public function serviceConfigFollowsTheCurrentConnection(): void
    {
        $other = self::createConnection();
        $otherService = self::createService();
        $context = Helper::withServiceConfigs(self::createContext(), self::serviceConfigs(
            [$other, $otherService],
        ));

        $modified = Helper::withCurrentConnection($context, $other);

        Assert::null(Helper::getServiceConfig($context));
        Assert::same(Helper::getServiceConfig($modified), $otherService);
    }

    public function serviceConfigIsNullWithoutServiceConfigs(): void
    {
        Assert::null(Helper::getServiceConfig(self::createContext()));
    }

    public function withServiceConfigsLeavesTheOriginalContextIntact(): void
    {
        $context = self::createContext();
        $service = self::createService();

        $modified = Helper::withServiceConfigs($context, self::serviceConfigs(
            [Helper::getCurrentConnection($context), $service],
        ));

        Assert::same(Helper::getServiceConfig($modified), $service);
        Assert::null(Helper::getServiceConfig($context));
        Assert::same($modified->getArguments(), $context->getArguments());
    }

    /**
     * Mirrors the argument layout built in {@see \Spiral\Grpc\Client\Internal\ServiceClient\ServiceClientTrait::_handle()}.
     */
    private static function createContext(): CallContext
    {
        return new CallContext(
            Target::fromClosure(static fn(): null => null),
            arguments: [
                self::createConnection(),
                '/service.Name/Method',
                new GPBEmpty(),
                [GPBEmpty::class, 'decode'],
                ['x-request-id' => ['42']],
                ['timeout' => 500],
            ],
        );
    }

    private static function createService(): ServiceConfig
    {
        return new ServiceConfig(new ConnectionConfig('127.0.0.1:1'));
    }

    /**
     * @param array{ConnectionInterface, ServiceConfig} ...$pairs
     */
    private static function serviceConfigs(array ...$pairs): ServiceConfigMap
    {
        return new ServiceConfigMap($pairs);
    }

    private static function createConnection(): ConnectionInterface
    {
        return new class implements ConnectionInterface {
            public function isConnected(): bool
            {
                return false;
            }

            public function disconnect(): void {}

            public function connect(float $timeout): void {}

            public function getStub(): ClientStub
            {
                throw new \LogicException('Not implemented.');
            }
        };
    }
}
