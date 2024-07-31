<?php

declare(strict_types=1);

namespace Spiral\Grpc\Client\Internal\ServiceClient;

use Spiral\Grpc\Client\Internal\Connection\Connection;
use Spiral\Grpc\Client\Internal\Connection\ConnectionInterface;
use Spiral\Interceptors\HandlerInterface;

interface ServiceClientInterface
{
    /**
     * @param list<ConnectionInterface> $connections Use {@see Connection} to create a connection.
     */
    public function __construct(
        HandlerInterface $pipeline,
        array $connections,
    );
}
