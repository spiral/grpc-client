<?php

declare(strict_types=1);

namespace Spiral\Grpc\Client\Config;

use Spiral\Core\Container\Autowire;
use Spiral\Grpc\Client\Interceptor\ExecuteServiceInterceptors;
use Spiral\Interceptors\InterceptorInterface as Interceptor;

final class ServiceConfig
{
    /** @var list<ConnectionConfig> */
    public readonly array $connections;

    /**
     * @param list<ConnectionConfig>|ConnectionConfig $connections Service connection and credentials.
     * @param list<Interceptor|class-string<Interceptor>|Autowire> $interceptors List of service specific
     *        interceptors to be used with the service.
     *        Place the {@see ExecuteServiceInterceptors} interceptor in the common pipeline
     *        to execute the service-specific interceptors in the correct order.
     * @param list<class-string> $interfaces List of registered gRPC interfaces.
     */
    public function __construct(
        ConnectionConfig|array $connections,
        public readonly array $interceptors = [],
        public readonly array $interfaces = [],
    ) {
        $this->connections = \is_array($connections) ? $connections : [$connections];
    }
}
