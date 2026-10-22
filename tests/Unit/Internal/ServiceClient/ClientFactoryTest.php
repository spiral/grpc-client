<?php

declare(strict_types=1);

namespace Spiral\Grpc\Client\Tests\Unit\Internal\ServiceClient;

use Spiral\Grpc\Client\Config\ConnectionConfig;
use Spiral\Grpc\Client\Config\ServiceConfig;
use Spiral\Grpc\Client\Internal\Registry\ServiceRegistry;
use Spiral\Grpc\Client\Internal\ServiceClient\ClientFactory;
use Spiral\Grpc\Client\Internal\ServiceClient\ServiceClientInterface;
use Spiral\Grpc\Client\Tests\Unit\Fixture\OtherServiceInterface;
use Spiral\Grpc\Client\Tests\Unit\Fixture\RecordingHandler;
use Spiral\Grpc\Client\Tests\Unit\Fixture\TestServiceInterface;
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
        $registry = new ServiceRegistry();
        $registry->addServices(new ServiceConfig(
            new ConnectionConfig('127.0.0.1:1'),
            interfaces: [TestServiceInterface::class],
        ));

        $client = (new ClientFactory($registry))->make(TestServiceInterface::class, self::handler());

        Assert::instanceOf($client, TestServiceInterface::class);
        Assert::instanceOf($client, ServiceClientInterface::class);
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

    private static function handler(): RecordingHandler
    {
        return new RecordingHandler(static fn(): null => null);
    }
}
