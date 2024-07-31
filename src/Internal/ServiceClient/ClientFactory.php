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

        // Get related Connections
        $connections = $this->fetchConnections($services);

        /** @see ServiceClientInterface::__construct() */
        return new $class($handler, $connections);
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
     * @param array<ServiceConfig> $services
     * @return array<ConnectionInterface>
     */
    private function fetchConnections(array $services): array
    {
        $result = [];
        foreach ($services as $service) {
            foreach ($service->connections as $connection) {
                $result[] = $this->registry->getConnection($connection);
            }
        }

        return $result;
    }
}
