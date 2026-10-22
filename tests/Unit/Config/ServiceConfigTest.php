<?php

declare(strict_types=1);

namespace Spiral\Grpc\Client\Tests\Unit\Config;

use Spiral\Grpc\Client\Config\ConnectionConfig;
use Spiral\Grpc\Client\Config\ServiceConfig;
use Testo\Assert;
use Testo\Codecov\Covers;
use Testo\Test;

#[Test]
#[Covers(ServiceConfig::class)]
final class ServiceConfigTest
{
    public function singleConnectionIsWrappedInList(): void
    {
        $connection = new ConnectionConfig('127.0.0.1:1');

        Assert::same((new ServiceConfig($connection))->connections, [$connection]);
    }

    public function connectionListIsKeptAsIs(): void
    {
        $connections = [new ConnectionConfig('127.0.0.1:1'), new ConnectionConfig('127.0.0.1:2')];

        Assert::same((new ServiceConfig($connections))->connections, $connections);
    }
}
