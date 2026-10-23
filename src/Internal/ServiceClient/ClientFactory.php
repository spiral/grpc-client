<?php

declare(strict_types=1);

namespace Spiral\Grpc\Client\Internal\ServiceClient;

use Spiral\Grpc\Client\Config\ServiceConfig;
use Spiral\Grpc\Client\Internal\Connection\ConnectionInterface;
use Spiral\Grpc\Client\Internal\Registry\ServiceRegistry;
use Spiral\Interceptors\HandlerInterface;

/**
 * Builder for gRPC service clients.
 *
 * @internal
 * @psalm-internal Spiral\Grpc\Client
 */
final class ClientFactory
{
    /** @var array<class-string, class-string> */
    private static array $cache = [];

    public function __construct(
        private readonly ServiceRegistry $registry,
    ) {}

    /**
     * @template T
     * @param class-string<T> $interface gRPC service interface.
     * @param HandlerInterface $handler Interceptor pipeline in a handler form.
     * @return T&ServiceClientInterface
     */
    public function make(string $interface, HandlerInterface $handler): object
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

        [$connections, $serviceConfigs] = $this->fetchConnections($services);

        /**
         * The generated class implements both the interface and {@see ServiceClientInterface}.
         *
         * @see ServiceClientInterface::__construct()
         * @var T&ServiceClientInterface $client
         */
        $client = new $class($handler, $connections, $serviceConfigs);
        return $client;
    }

    /**
     * @template T
     * @param class-string<T> $service
     * @return class-string<T&ServiceClientInterface>
     */
    private function prepareClientClass(string $service): string
    {
        [$className, $classCode] = ClassGenerator::generate($service);

        eval($classCode);

        return $className;
    }

    /**
     * Collect the connections of the services and map each connection to its service.
     *
     * The map is built per interface: services of other interfaces may share a connection
     * and must not lend it their interceptors.
     *
     * @param list<ServiceConfig> $services
     * @return array{list<ConnectionInterface>, ServiceConfigMap}
     */
    private function fetchConnections(array $services): array
    {
        $connections = [];
        $pairs = [];
        foreach ($services as $service) {
            foreach ($service->connections as $connectionConfig) {
                $connection = $this->registry->getConnection($connectionConfig);
                $connections[] = $connection;
                $pairs[] = [$connection, $service];
            }
        }

        return [$connections, new ServiceConfigMap($pairs)];
    }
}
