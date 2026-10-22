<?php

declare(strict_types=1);

namespace Spiral\Grpc\Client\Tests\Unit;

use Google\Protobuf\GPBEmpty;
use Spiral\Core\Container\Autowire;
use Spiral\Core\FactoryInterface;
use Spiral\Grpc\Client\Config\ConnectionConfig;
use Spiral\Grpc\Client\GrpcClient;
use Spiral\Grpc\Client\Interceptor\Helper;
use Spiral\Grpc\Client\Tests\Acceptance\GrpcContext;
use Spiral\Grpc\Client\Tests\Unit\Fixture\ShortCircuitInterceptor;
use Spiral\Grpc\Client\Tests\Unit\Fixture\TestServiceInterface;
use Spiral\Interceptors\Context\CallContextInterface;
use Spiral\Interceptors\HandlerInterface;
use Spiral\Interceptors\InterceptorInterface;
use Spiral\Interceptors\PipelineBuilderInterface;
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

    public function withPipelineBuilderIsUsedToBuildTheHandler(): void
    {
        $builder = new StubPipelineBuilder();
        $client = GrpcClient::create('localhost:9001');

        $new = $client->withPipelineBuilder($builder);
        $new->service(StubServiceInterface::class);

        Assert::notSame($client, $new);
        Assert::true($builder->built);
    }

    public function withFactoryInvalidatesCacheOnNewInstance(): void
    {
        $client = GrpcClient::create('localhost:9001');
        $first = $client->service(StubServiceInterface::class);

        $second = $client->withFactory(new StubFactory())->service(StubServiceInterface::class);

        Assert::notSame($second, $first);
    }

    public function withPipelineBuilderInvalidatesCacheOnNewInstance(): void
    {
        $client = GrpcClient::create('localhost:9001');
        $first = $client->service(StubServiceInterface::class);

        $second = $client->withPipelineBuilder(new StubPipelineBuilder())->service(StubServiceInterface::class);

        Assert::notSame($second, $first);
    }

    public function oneClientServesSeveralInterfaces(): void
    {
        $client = GrpcClient::create('localhost:9001');

        Assert::instanceOf($client->service(StubServiceInterface::class), StubServiceInterface::class);
        Assert::instanceOf($client->service(TestServiceInterface::class), TestServiceInterface::class);
    }

    public function interceptorsAreResolvedOncePerClient(): void
    {
        $factory = new StubFactory();
        $client = GrpcClient::create('localhost:9001')
            ->withFactory($factory)
            ->withInterceptors([StubInterceptor::class]);

        $client->service(StubServiceInterface::class);
        $client->service(TestServiceInterface::class);

        Assert::same($factory->calls, 1);
    }

    public function everyEndpointBecomesAConnection(): void
    {
        $interceptor = new ShortCircuitInterceptor();
        $client = GrpcClient::create(['127.0.0.1:1', new ConnectionConfig('127.0.0.1:2'), '127.0.0.1:3'])
            ->withInterceptors([$interceptor]);

        $client->service(TestServiceInterface::class)->Ping(new GrpcContext(), new GPBEmpty());

        Assert::count(Helper::getConnections($interceptor->contexts[0]), 3);
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
    public function Ping(\Spiral\RoadRunner\GRPC\ContextInterface $ctx, GPBEmpty $in): GPBEmpty;
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
final class StubPipelineBuilder implements PipelineBuilderInterface
{
    public bool $built = false;

    public function withInterceptors(InterceptorInterface ...$interceptors): static
    {
        return $this;
    }

    public function build(HandlerInterface $handler): HandlerInterface
    {
        $this->built = true;
        return $handler;
    }
}

/**
 * @internal
 */
final class StubFactory implements FactoryInterface
{
    public bool $called = false;
    public int $calls = 0;

    public function make(string $alias, array $parameters = []): mixed
    {
        $this->called = true;
        ++$this->calls;
        return new $alias(...$parameters);
    }
}
