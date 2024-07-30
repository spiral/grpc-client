<?php

declare(strict_types=1);

namespace Spiral\Grpc\Client\Config;

final class ServiceConfig
{
    /**
     * @param list<ConnectionConfig>|ConnectionConfig $connections Service connection and credentials.
     * @param list<class-string> $interfaces List of registered gRPC interfaces.
     */
    public function __construct(
        public readonly ConnectionConfig|array $connections,
        public readonly array $options = [], // todo
        public readonly array $interfaces = [],
    ) {}
}
