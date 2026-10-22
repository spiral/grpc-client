<?php

declare(strict_types=1);

namespace Spiral\Grpc\Client\Tests\Unit\Fixture;

use Spiral\Grpc\Client\Internal\Connection\ClientStub;
use Spiral\Grpc\Client\Internal\Connection\ConnectionInterface;

final class FakeConnection implements ConnectionInterface
{
    public function __construct(
        private readonly bool $connected = false,
    ) {}

    public function isConnected(): bool
    {
        return $this->connected;
    }

    public function disconnect(): void {}

    public function connect(float $timeout): void {}

    public function getStub(): ClientStub
    {
        throw new \LogicException('A fake connection has no stub.');
    }
}
