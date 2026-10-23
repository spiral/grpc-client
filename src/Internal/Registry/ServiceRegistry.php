<?php

declare(strict_types=1);

namespace Spiral\Grpc\Client\Internal\Registry;

use Spiral\Grpc\Client\Config\ConnectionConfig;
use Spiral\Grpc\Client\Config\ServiceConfig;
use Spiral\Grpc\Client\Internal\Connection\Connection;
use Spiral\Grpc\Client\Internal\Connection\ConnectionInterface;

/**
 * @internal
 */
final class ServiceRegistry
{
    /**
     * @var array<class-string, list<ServiceConfig>>
     */
    private array $serviceMap = [];

    /**
     * Connections cache where key is a connection config object id.
     * @var array<int, Connection>
     */
    private array $connectionConfigToConnection = [];

    public function addServices(ServiceConfig ...$configs): void
    {
        foreach ($configs as $config) {
            $this->addService($config);
        }
    }

    /**
     * @return list<ServiceConfig>
     */
    public function getServices(string $interface): array
    {
        return $this->serviceMap[$interface] ?? [];
    }

    public function getConnection(ConnectionConfig $connection): ConnectionInterface
    {
        // Use cache
        $index = \spl_object_id($connection);
        if (\array_key_exists($index, $this->connectionConfigToConnection)) {
            return $this->connectionConfigToConnection[$index];
        }

        // Add to cache
        $result = new Connection($connection);
        $this->connectionConfigToConnection[$index] = $result;

        return $result;
    }

    private function addService(ServiceConfig $config): void
    {
        foreach ($config->interfaces as $interface) {
            $this->serviceMap[$interface][] = $config;
        }
    }
}
