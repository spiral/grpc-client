<?php

declare(strict_types=1);

namespace Spiral\Grpc\Client\Internal\ServiceClient;

use Spiral\Grpc\Client\Internal\Connection\Connection;
use Spiral\Interceptors\PipelineBuilderInterface;

interface ServiceClientInterface
{
    /**
     * @param list<Connection> $connections
     */
    public function __construct(
        PipelineBuilderInterface $pipeline,
        array $connections,
    );
}
