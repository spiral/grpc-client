<?php

declare(strict_types=1);

namespace Spiral\Grpc\Client\Internal\ServiceClient;

use Spiral\Grpc\Client\Internal\Connection\Connection;
use Spiral\Grpc\Client\Internal\Connection\ConnectionInterface;
use Spiral\Interceptors\HandlerInterface;

/**
 * @internal
 * @psalm-internal Spiral\Grpc\Client
 */
interface ServiceClientInterface
{
    /**
     * @param list<ConnectionInterface> $connections Use {@see Connection} to create a connection.
     * @param ServiceConfigMap $serviceConfigs Config of the service behind each connection.
     */
    public function __construct(
        HandlerInterface $pipeline,
        array $connections,
        ServiceConfigMap $serviceConfigs,
    );
}
