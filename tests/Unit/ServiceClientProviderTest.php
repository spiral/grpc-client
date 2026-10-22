<?php

declare(strict_types=1);

namespace Spiral\Grpc\Client\Tests\Unit;

use Google\Protobuf\GPBEmpty;
use Spiral\Core\Container;
use Spiral\Core\Container\Autowire;
use Spiral\Grpc\Client\Config\ConnectionConfig;
use Spiral\Grpc\Client\Config\GrpcClientConfig;
use Spiral\Grpc\Client\Config\ServiceConfig;
use Spiral\Grpc\Client\Interceptor\Helper;
use Spiral\Grpc\Client\ServiceClientProvider;
use Spiral\Grpc\Client\Tests\Acceptance\GrpcContext;
use Spiral\Grpc\Client\Tests\Unit\Fixture\OtherServiceInterface;
use Spiral\Grpc\Client\Tests\Unit\Fixture\RecordingInterceptor;
use Spiral\Grpc\Client\Tests\Unit\Fixture\ShortCircuitInterceptor;
use Spiral\Grpc\Client\Tests\Unit\Fixture\TestServiceInterface;
use Testo\Assert;
use Testo\Codecov\Covers;
use Testo\Test;

#[Test]
#[Covers(ServiceClientProvider::class)]
final class ServiceClientProviderTest
{
    public function clientInterfacesAreCollectedFromAllServicesWithoutDuplicates(): void
    {
        $connection = new ConnectionConfig('127.0.0.1:1');
        $provider = self::provider(new GrpcClientConfig(services: [
            new ServiceConfig($connection, interfaces: [TestServiceInterface::class, OtherServiceInterface::class]),
            new ServiceConfig($connection, interfaces: [TestServiceInterface::class]),
        ]));

        Assert::same(
            \array_values($provider->getClientInterfaces()),
            [TestServiceInterface::class, OtherServiceInterface::class],
        );
    }

    public function noServicesMeansNoInterfaces(): void
    {
        Assert::same(self::provider(new GrpcClientConfig())->getClientInterfaces(), []);
    }

    public function serviceClientRunsCommonInterceptorsInOrder(): void
    {
        RecordingInterceptor::$log = [];
        $terminal = new ShortCircuitInterceptor();
        $connection = new ConnectionConfig('127.0.0.1:1');
        $provider = self::provider(new GrpcClientConfig(
            interceptors: [
                RecordingInterceptor::class,
                new Autowire(RecordingInterceptor::class, ['name' => 'autowired']),
                $terminal,
            ],
            services: [new ServiceConfig($connection, interfaces: [TestServiceInterface::class])],
        ));

        $client = $provider->getServiceClient(TestServiceInterface::class);
        $response = $client->Ping(new GrpcContext(), new GPBEmpty());

        Assert::instanceOf($client, TestServiceInterface::class);
        Assert::instanceOf($response, GPBEmpty::class);
        Assert::same(RecordingInterceptor::$log, ['default', 'autowired']);
        Assert::count($terminal->contexts, 1);
        Assert::same($terminal->contexts[0]->getArguments()[1], '/test.Service/Ping');
        Assert::count(Helper::getConnections($terminal->contexts[0]), 1);
    }

    private static function provider(GrpcClientConfig $config): ServiceClientProvider
    {
        return new ServiceClientProvider($config, new Container());
    }
}
