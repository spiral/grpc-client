<?php

declare(strict_types=1);

namespace Spiral\Grpc\Client\Internal;

use Spiral\Grpc\Client\Config\ServiceConfig;
use Spiral\Grpc\Client\Internal\Connection\Connection;
use Spiral\Grpc\Client\Internal\Interceptor\GrpcServiceCallHandler;
use Spiral\Grpc\Client\Internal\Registry\ServiceRegistry;
use Spiral\Grpc\Client\Internal\ServiceClient\ClassGenerator;
use Spiral\Grpc\Client\Internal\ServiceClient\ServiceClientInterface;
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
        PipelineBuilderInterface $pipelineBuilder,
        private readonly ServiceRegistry $registry,
    ) {
        // Create pipeline builder with common interceptors
        $this->pipelineBuilder = $pipelineBuilder->withInterceptors(
            // todo
        );
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
     * @param list<ServiceConfig> $services
     * @return array<Connection>
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
