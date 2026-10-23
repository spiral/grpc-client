<?php

declare(strict_types=1);

namespace Spiral\Grpc\Client\Tests\Unit\Internal\Registry;

use Spiral\Grpc\Client\Config\ConnectionConfig;
use Spiral\Grpc\Client\Config\ServiceConfig;
use Spiral\Grpc\Client\Internal\Registry\ServiceRegistry;
use Spiral\Grpc\Client\Tests\Unit\Fixture\OtherServiceInterface;
use Spiral\Grpc\Client\Tests\Unit\Fixture\TestServiceInterface;
use Testo\Assert;
use Testo\Codecov\Covers;
use Testo\Test;

#[Test]
#[Covers(ServiceRegistry::class)]
final class ServiceRegistryTest
{
    public function servicesAreGroupedByInterface(): void
    {
        $first = new ServiceConfig(new ConnectionConfig('127.0.0.1:1'), interfaces: [TestServiceInterface::class]);
        $second = new ServiceConfig(
            new ConnectionConfig('127.0.0.1:2'),
            interfaces: [TestServiceInterface::class, OtherServiceInterface::class],
        );
        $registry = new ServiceRegistry();
        $registry->addServices($first, $second);

        Assert::same($registry->getServices(TestServiceInterface::class), [$first, $second]);
        Assert::same($registry->getServices(OtherServiceInterface::class), [$second]);
        Assert::same($registry->getServices(\Countable::class), []);
    }

    public function connectionIsCreatedOncePerConfig(): void
    {
        $config = new ConnectionConfig('127.0.0.1:1');
        $registry = new ServiceRegistry();

        $connection = $registry->getConnection($config);

        Assert::same($registry->getConnection($config), $connection);
        Assert::notSame($registry->getConnection(new ConnectionConfig('127.0.0.1:1')), $connection);
    }
}
