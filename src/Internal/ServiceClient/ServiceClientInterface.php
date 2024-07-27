<?php

declare(strict_types=1);

namespace Spiral\Grpc\Client\Internal\ServiceClient;

use Spiral\Grpc\Client\Internal\Connection\Connection;
use Spiral\Interceptors\PipelineBuilderInterface;

interface ServiceClientInterface
{
    public function __construct(
        Connection $connection,
        PipelineBuilderInterface $pipeline,
    );
}
