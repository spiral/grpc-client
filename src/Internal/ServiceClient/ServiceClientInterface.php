<?php

declare(strict_types=1);

namespace Spiral\Grpc\Client\Internal\ServiceClient;

use Spiral\Grpc\Client\Internal\Connection\Connection;
use Spiral\Grpc\Client\Internal\Connection\ConnectionInterface;
use Spiral\Interceptors\PipelineBuilderInterface;

interface ServiceClientInterface
{
    /**
     * @param list<ConnectionInterface> $connections Use {@see Connection} to create a connection.
     */
    public function __construct(
        PipelineBuilderInterface $pipeline,
        array $connections,
    );
}
