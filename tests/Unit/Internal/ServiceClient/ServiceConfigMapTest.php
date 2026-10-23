<?php

declare(strict_types=1);

namespace Spiral\Grpc\Client\Tests\Unit\Internal\ServiceClient;

use Spiral\Grpc\Client\Config\ConnectionConfig;
use Spiral\Grpc\Client\Config\ServiceConfig;
use Spiral\Grpc\Client\Internal\ServiceClient\ServiceConfigMap;
use Spiral\Grpc\Client\Tests\Unit\Fixture\FakeConnection;
use Testo\Assert;
use Testo\Codecov\Covers;
use Testo\Test;

#[Test]
#[Covers(ServiceConfigMap::class)]
final class ServiceConfigMapTest
{
    public function connectionResolvesToItsService(): void
    {
        [$first, $second] = [new FakeConnection(), new FakeConnection()];
        [$firstService, $secondService] = [self::service(), self::service()];

        $map = new ServiceConfigMap([[$first, $firstService], [$second, $secondService]]);

        Assert::same($map->get($first), $firstService);
        Assert::same($map->get($second), $secondService);
    }

    public function unknownConnectionHasNoService(): void
    {
        $map = new ServiceConfigMap([[new FakeConnection(), self::service()]]);

        Assert::null($map->get(new FakeConnection()));
        Assert::null((new ServiceConfigMap())->get(new FakeConnection()));
    }

    public function repeatedConnectionKeepsItsFirstService(): void
    {
        $connection = new FakeConnection();
        $first = self::service();

        $map = new ServiceConfigMap([[$connection, $first], [$connection, self::service()]]);

        Assert::same($map->get($connection), $first);
    }

    public function pairsMayComeFromAnyIterable(): void
    {
        $connection = new FakeConnection();
        $service = self::service();

        $map = new ServiceConfigMap((static function () use ($connection, $service): \Generator {
            yield [$connection, $service];
        })());

        Assert::same($map->get($connection), $service);
    }

    private static function service(): ServiceConfig
    {
        return new ServiceConfig(new ConnectionConfig('127.0.0.1:1'));
    }
}
