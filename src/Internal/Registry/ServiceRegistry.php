<?php

declare(strict_types=1);

namespace Spiral\Grpc\Client\Internal\Registry;

use Spiral\Grpc\Client\Config\ConnectionConfig;
use Spiral\Grpc\Client\Config\ServiceConfig;
use Spiral\Grpc\Client\Internal\Connection\Connection;
use Spiral\Grpc\Client\Internal\Connection\ConnectionInterface;

final class ServiceRegistry
{
    /**
     * @var array<class-string, list<ServiceConfig>>
     */
    private array $serviceMap = [];

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

    private function addService(ServiceConfig $config): void
    {
        foreach ($config->interfaces as $interface) {
            $this->serviceMap[$interface][] = $config;
        }
    }

    public function getConnection(ConnectionConfig $connection): ConnectionInterface
    {
        return new Connection($connection);
    }
}
