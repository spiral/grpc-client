<?php

declare(strict_types=1);

namespace Spiral\Grpc\Client\Internal\ServiceClient;

use Spiral\Core\Container\Autowire;
use Spiral\Core\FactoryInterface;
use Spiral\Grpc\Client\Config\GrpcClientConfig;
use Spiral\Grpc\Client\Config\ServiceConfig;
use Spiral\Grpc\Client\Internal\Connection\ConnectionInterface;
use Spiral\Grpc\Client\Internal\Interceptor\GrpcServiceCallHandler;
use Spiral\Grpc\Client\Internal\Registry\ServiceRegistry;
use Spiral\Interceptors\PipelineBuilderInterface;

/**
 *Builder for gRPC service clients.
 */
final class Builder
{
    /** @var array<class-string, class-string<ServiceClientInterface>> */
    private static array $cache = [];
    /**
     * Pipeline builder with prepared common interceptors.
     */
    private readonly PipelineBuilderInterface $pipelineBuilder;

    public function __construct(
        private readonly ServiceRegistry $registry,
        PipelineBuilderInterface $pipelineBuilder,
        GrpcClientConfig $config,
        ?FactoryInterface $container = null,
    ) {
        // Prepare common interceptors
        if ($container !== null) {
            $list = [];
            foreach ($config->interceptors as $interceptor) {
                $list[] = \is_string($interceptor) || $interceptor instanceof Autowire
                    ? $container->make($interceptor)
                    : $interceptor;
            }
        }

        // Create pipeline builder with common interceptors
        $this->pipelineBuilder = $pipelineBuilder->withInterceptors(...$list);
    }

    /**
     * @template T
     * @param class-string<T> $interface gRPC service interface.
     * @return T
     */
    public function build(string $interface): object
    {
        \interface_exists($interface, true) or throw new \InvalidArgumentException(
            "Service interface not found: $interface",
        );

        // Get related server endpoint
        $services = $this->registry->getServices($interface);
        $services === [] and throw new \RuntimeException(
            "Service not found for the interface `$interface`.",
        );

        /** @var class-string<ServiceClientInterface> $class */
        $class = self::$cache[$interface] ??= $this->prepareClientClass($interface);

        // Get related Connections
        $connections = $this->fetchConnections($services);

        // Interceptors pipeline
        $handler = $this->pipelineBuilder->build(new GrpcServiceCallHandler());

        /** @see ServiceClientInterface::__construct() */
        return new $class($handler, $connections);
    }

    /**
     * @template T
     * @param class-string<T> $service
     * @return class-string<T&ServiceClientInterface>
     */
    public function prepareClientClass(string $service): string
    {
        [$className, $classCode] = ClassGenerator::generate($service);

        eval($classCode);

        return $className;
    }

    /**
     * @param ServiceConfig $services
     * @return array<ConnectionInterface>
     */
    private function fetchConnections(array $services): array
    {
        $connections = [];
        foreach ($services as $service) {
            $connections[] = $this->registry->getConnection($service->connection);
        }

        return $connections;
    }
}
