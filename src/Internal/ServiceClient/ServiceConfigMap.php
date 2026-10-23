<?php

declare(strict_types=1);

namespace Spiral\Grpc\Client\Internal\ServiceClient;

use Spiral\Grpc\Client\Config\ServiceConfig;
use Spiral\Grpc\Client\Internal\Connection\ConnectionInterface;

/**
 * Config of the service behind each connection of a service client.
 *
 * Every call context of the client shares this object, so it exposes no way to change it.
 *
 * @internal
 * @psalm-internal Spiral\Grpc\Client
 */
final readonly class ServiceConfigMap
{
    /** @var \WeakMap<ConnectionInterface, ServiceConfig> */
    private \WeakMap $map;

    /**
     * @param iterable<array{ConnectionInterface, ServiceConfig}> $pairs
     *        A connection listed several times belongs to the first of its services.
     */
    public function __construct(iterable $pairs = [])
    {
        /** @var \WeakMap<ConnectionInterface, ServiceConfig> $map */
        $map = new \WeakMap();
        foreach ($pairs as [$connection, $service]) {
            isset($map[$connection]) or $map[$connection] = $service;
        }

        $this->map = $map;
    }

    public function get(ConnectionInterface $connection): ?ServiceConfig
    {
        return $this->map[$connection] ?? null;
    }
}
