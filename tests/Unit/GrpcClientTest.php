<?php

declare(strict_types=1);

namespace Spiral\Grpc\Client\Tests\Unit;

use Spiral\Core\Container\Autowire;
use Spiral\Core\FactoryInterface;
use Spiral\Grpc\Client\Config\ConnectionConfig;
use Spiral\Grpc\Client\GrpcClient;
use Spiral\Interceptors\Context\CallContextInterface;
use Spiral\Interceptors\HandlerInterface;
use Spiral\Interceptors\InterceptorInterface;
use Testo\Assert;
use Testo\Expect;
use Testo\Test;

#[Test]
final class GrpcClientTest
{
    public function createWithStringEndpoint(): void
    {
        $client = GrpcClient::create('localhost:9001');

        Assert::instanceOf($client, GrpcClient::class);
    }

    public function createWithConnectionConfig(): void
    {
        $client = GrpcClient::create(new ConnectionConfig('localhost:9001'));

        Assert::instanceOf($client, GrpcClient::class);
    }

    public function createWithArray(): void
    {
        $client = GrpcClient::create([
            'localhost:9001',
            new ConnectionConfig('localhost:9002'),
            'localhost:9003',
        ]);

        Assert::instanceOf($client, GrpcClient::class);
    }

    public function createWithEmptyArrayThrows(): void
    {
        Expect::exception(\InvalidArgumentException::class);

        GrpcClient::create([]);
    }

    public function withInterceptorsReturnsNewInstance(): void
    {
        $client = GrpcClient::create('localhost:9001');
        $new = $client->withInterceptors([]);

        Assert::notSame($client, $new);
    }

    public function withFactoryReturnsNewInstance(): void
    {
        $factory = new class implements FactoryInterface {
            public function make(string $alias, array $parameters = []): mixed
            {
                return new $alias(...$parameters);
            }
        };

        $client = GrpcClient::create('localhost:9001');
        $new = $client->withFactory($factory);

        Assert::notSame($client, $new);
    }

    public function serviceReturnsCachedInstance(): void
    {
        $client = GrpcClient::create('localhost:9001');
        $first = $client->service(StubServiceInterface::class);
        $second = $client->service(StubServiceInterface::class);

        Assert::same($first, $second);
    }

    public function withInterceptorsInvalidatesCacheOnNewInstance(): void
    {
        $client = GrpcClient::create('localhost:9001');
        $first = $client->service(StubServiceInterface::class);

        $new = $client->withInterceptors([]);
        $second = $new->service(StubServiceInterface::class);

        // Original still returns the same cached instance
        Assert::same($first, $client->service(StubServiceInterface::class));
        // New instance has a different service object
        Assert::notSame($first, $second);
    }

    public function autowireInterceptorResolvesWithoutFactory(): void
    {
        $interceptor = new Autowire(StubInterceptor::class, []);
        $client = GrpcClient::create('localhost:9001')
            ->withInterceptors([$interceptor]);

        $service = $client->service(StubServiceInterface::class);

        Assert::instanceOf($service, StubServiceInterface::class);
    }

    public function classStringInterceptorWithoutFactory(): void
    {
        $client = GrpcClient::create('localhost:9001')
            ->withInterceptors([StubInterceptor::class]);

        $service = $client->service(StubServiceInterface::class);

        Assert::instanceOf($service, StubServiceInterface::class);
    }

    public function classStringInterceptorWithCustomFactory(): void
    {
        $factory = new StubFactory();

        $client = GrpcClient::create('localhost:9001')
            ->withFactory($factory)
            ->withInterceptors([StubInterceptor::class]);

        $client->service(StubServiceInterface::class);

        Assert::true($factory->called);
    }

    public function noMemoryLeaksAfterUnset(): void
    {
        $interceptor = new StubInterceptor();
        Expect::notLeaks($interceptor);

        $client = GrpcClient::create('localhost:9001')
            ->withInterceptors([$interceptor]);

        $client->service(StubServiceInterface::class);

        unset($client);
    }
}

/**
 * Minimal gRPC service interface for testing.
 * @internal
 */
interface StubServiceInterface
{
    public function Ping(\Spiral\RoadRunner\GRPC\ContextInterface $ctx, \Google\Protobuf\GPBEmpty $in): \Google\Protobuf\GPBEmpty;
}

/**
 * @internal
 */
final class StubInterceptor implements InterceptorInterface
{
    public function intercept(CallContextInterface $context, HandlerInterface $handler): mixed
    {
        return $handler->handle($context);
    }
}

/**
 * @internal
 */
final class StubFactory implements FactoryInterface
{
    public bool $called = false;

    public function make(string $alias, array $parameters = []): mixed
    {
        $this->called = true;
        return new $alias(...$parameters);
    }
}
