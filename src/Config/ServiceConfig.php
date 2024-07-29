<?php

declare(strict_types=1);

namespace Spiral\Grpc\Client\Config;

final class ServiceConfig
{
    /**
     * @param ConnectionConfig $connection Service connection and credentials configuration.
     * @param list<class-string> $interfaces List of registered gRPC interfaces.
     */
    public function __construct(
        public readonly ConnectionConfig $connection,
        public readonly array $options = [], // todo
        public readonly array $interfaces = [],
    ) {}
}
