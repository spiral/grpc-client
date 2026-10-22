<?php

declare(strict_types=1);

namespace Spiral\Grpc\Client\Tests\Unit\Internal\Connection;

use Spiral\Grpc\Client\Config\ConnectionConfig;
use Spiral\Grpc\Client\Internal\Connection\ClientStub;
use Spiral\Grpc\Client\Internal\Connection\Connection;
use Testo\Assert;
use Testo\Codecov\Covers;
use Testo\Core\Exception\SkipTest;
use Testo\Expect;
use Testo\Test;

/**
 * Connections to a closed local port: nothing listens there, so every connect attempt fails.
 */
#[Test]
#[Covers(Connection::class)]
final class ConnectionTest
{
    private const DEAD_ADDRESS = '127.0.0.1:1';

    public function newConnectionIsNotConnected(): void
    {
        self::skipOnWindows();

        $connection = new Connection(new ConnectionConfig(self::DEAD_ADDRESS));

        Assert::false($connection->isConnected());
        Assert::instanceOf($connection->getStub(), ClientStub::class);
    }

    public function stubIsReusedUntilDisconnect(): void
    {
        self::skipOnWindows();

        $connection = new Connection(new ConnectionConfig(self::DEAD_ADDRESS));
        $stub = $connection->getStub();

        Assert::same($connection->getStub(), $stub);

        $connection->disconnect();
        $connection->disconnect();

        Assert::notSame($connection->getStub(), $stub);
    }

    public function connectToUnreachableServerFails(): void
    {
        self::skipOnWindows();

        Expect::exception(\RuntimeException::class)->withMessageContaining('Failed to connect to the service');

        (new Connection(new ConnectionConfig(self::DEAD_ADDRESS)))->connect(5.0);
    }

    public function connectInsideFiberSuspendsWhileWaiting(): void
    {
        self::skipOnWindows();

        $connection = new Connection(new ConnectionConfig(self::DEAD_ADDRESS));
        $fiber = new \Fiber(static fn() => $connection->connect(5.0));

        $error = null;
        $suspensions = 0;
        try {
            $fiber->start();
            while (!$fiber->isTerminated()) {
                ++$suspensions;
                \usleep(1000);
                $fiber->resume();
            }
        } catch (\RuntimeException $e) {
            $error = $e;
        }

        Assert::instanceOf($error, \RuntimeException::class);
        Assert::true($suspensions > 0);
    }

    private static function skipOnWindows(): void
    {
        \DIRECTORY_SEPARATOR === '\\' and throw new SkipTest(
            'On Windows ext-grpc segfaults at process shutdown after channels to unreachable addresses in a full run.',
        );
    }
}
