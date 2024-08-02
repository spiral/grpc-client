<?php

declare(strict_types=1);

namespace Spiral\Grpc\Client\Config;

use Spiral\Core\Container\Autowire;
use Spiral\Interceptors\InterceptorInterface as Interceptor;

final class GrpcClientConfig
{
    /**
     * @param array<class-string<Interceptor>|Autowire<Interceptor>|Interceptor> $interceptors List
     *        of common gRPC interceptors to be used with all services.
     * @param array<ServiceConfig> $services List of gRPC service configurations.
     */
    public function __construct(
        public readonly array $interceptors = [],
        public readonly array $services = [],
    ) {}
}
