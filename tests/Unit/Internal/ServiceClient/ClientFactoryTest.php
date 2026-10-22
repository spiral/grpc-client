<?php

declare(strict_types=1);

namespace Spiral\Grpc\Client\Tests\Unit\Internal\ServiceClient;

use Google\Protobuf\GPBEmpty;
use Spiral\Grpc\Client\Config\ConnectionConfig;
use Spiral\Grpc\Client\Config\ServiceConfig;
use Spiral\Grpc\Client\Interceptor\Helper;
use Spiral\Grpc\Client\Internal\Registry\ServiceRegistry;
use Spiral\Grpc\Client\Internal\ServiceClient\ClientFactory;
use Spiral\Grpc\Client\Internal\ServiceClient\ServiceClientInterface;
use Spiral\Grpc\Client\Tests\Acceptance\GrpcContext;
use Spiral\Grpc\Client\Tests\Unit\Fixture\OtherServiceInterface;
use Spiral\Grpc\Client\Tests\Unit\Fixture\RecordingHandler;
use Spiral\Grpc\Client\Tests\Unit\Fixture\ShortCircuitInterceptor;
use Spiral\Grpc\Client\Tests\Unit\Fixture\TestServiceInterface;
use Spiral\Interceptors\PipelineBuilder;
use Testo\Assert;
use Testo\Codecov\Covers;
use Testo\Expect;
use Testo\Test;

#[Test]
#[Covers(ClientFactory::class)]
final class ClientFactoryTest
{
    public function clientImplementsServiceInterface(): void
    {
        $client = (new ClientFactory(self::registry()))->make(TestServiceInterface::class, self::handler());

        Assert::instanceOf($client, TestServiceInterface::class);
        Assert::instanceOf($client, ServiceClientInterface::class);
    }

    public function clientClassIsGeneratedOncePerInterface(): void
    {
        $registry = self::registry();

        $first = (new ClientFactory($registry))->make(TestServiceInterface::class, self::handler());
        $second = (new ClientFactory($registry))->make(TestServiceInterface::class, self::handler());

        Assert::notSame($second, $first);
        Assert::same($second::class, $first::class);
    }

    public function clientReceivesConnectionsOfAllMatchingServices(): void
    {
        $interceptor = new ShortCircuitInterceptor();
        $handler = (new PipelineBuilder())->withInterceptors($interceptor)->build(self::handler());

        (new ClientFactory(self::registry(
            new ServiceConfig(
                [new ConnectionConfig('127.0.0.1:1'), new ConnectionConfig('127.0.0.1:2')],
                interfaces: [TestServiceInterface::class],
            ),
            new ServiceConfig(new ConnectionConfig('127.0.0.1:3'), interfaces: [TestServiceInterface::class]),
        )))
            ->make(TestServiceInterface::class, $handler)
            ->Ping(new GrpcContext(), new GPBEmpty());

        Assert::count(Helper::getConnections($interceptor->contexts[0]), 3);
    }

    public function unknownInterfaceIsRejected(): void
    {
        Expect::exception(\InvalidArgumentException::class)
            ->withMessageContaining('Service interface not found');

        (new ClientFactory(new ServiceRegistry()))->make('Missing\\ServiceInterface', self::handler());
    }

    public function interfaceWithoutServiceIsRejected(): void
    {
        Expect::exception(\RuntimeException::class)
            ->withMessageContaining(OtherServiceInterface::class);

        (new ClientFactory(new ServiceRegistry()))->make(OtherServiceInterface::class, self::handler());
    }

    private static function registry(ServiceConfig ...$services): ServiceRegistry
    {
        $registry = new ServiceRegistry();
        $registry->addServices(...($services ?: [
            new ServiceConfig(new ConnectionConfig('127.0.0.1:1'), interfaces: [TestServiceInterface::class]),
        ]));

        return $registry;
    }

    private static function handler(): RecordingHandler
    {
        return new RecordingHandler(static fn(): null => null);
    }
}
