<?php

declare(strict_types=1);

namespace Spiral\Grpc\Client\Tests\Unit\Bridge;

use Spiral\Core\Container;
use Spiral\Grpc\Client\Bridge\GrpcClientBootloader;
use Spiral\Grpc\Client\Config\ConnectionConfig;
use Spiral\Grpc\Client\Config\GrpcClientConfig;
use Spiral\Grpc\Client\Config\ServiceConfig;
use Spiral\Grpc\Client\ServiceClientProvider;
use Spiral\Grpc\Client\Tests\Unit\Fixture\OtherServiceInterface;
use Spiral\Grpc\Client\Tests\Unit\Fixture\TestServiceInterface;
use Testo\Assert;
use Testo\Codecov\Covers;
use Testo\Test;

#[Test]
#[Covers(GrpcClientBootloader::class)]
final class GrpcClientBootloaderTest
{
    public function everyConfiguredInterfaceIsBoundAsSingleton(): void
    {
        $container = new Container();
        $container->bindSingleton(GrpcClientConfig::class, new GrpcClientConfig(services: [
            new ServiceConfig(
                new ConnectionConfig('127.0.0.1:1'),
                interfaces: [TestServiceInterface::class, OtherServiceInterface::class],
            ),
        ]));

        (new GrpcClientBootloader())->boot($container, $container->get(ServiceClientProvider::class));

        $client = $container->get(TestServiceInterface::class);
        Assert::instanceOf($client, TestServiceInterface::class);
        Assert::same($container->get(TestServiceInterface::class), $client);
        Assert::instanceOf($container->get(OtherServiceInterface::class), OtherServiceInterface::class);
    }
}
