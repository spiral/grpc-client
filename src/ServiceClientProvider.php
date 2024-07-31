<?php

declare(strict_types=1);

namespace Spiral\Grpc\Client;

use Spiral\Core\Attribute\Singleton;
use Spiral\Core\Container\Autowire;
use Spiral\Core\FactoryInterface;
use Spiral\Grpc\Client\Config\GrpcClientConfig;
use Spiral\Grpc\Client\Internal\Registry\ServiceRegistry;
use Spiral\Grpc\Client\Internal\ServiceClient\Builder;
use Spiral\Interceptors\InterceptorInterface;
use Spiral\Interceptors\PipelineBuilderInterface;

#[Singleton]
final class ServiceClientProvider
{
    private readonly Builder $builder;

    public function __construct(
        private readonly GrpcClientConfig $config,
        PipelineBuilderInterface $pipelineBuilder,
        FactoryInterface $factory,
    ) {
        // Collect all the services
        $registry = new ServiceRegistry();
        $registry->addServices(...$this->config->services);

        // Prepare common interceptors
        $list = [];
        foreach ($config->interceptors as $interceptor) {
            $list[] = match (true) {
                \is_string($interceptor) => $factory->make($interceptor),
                $interceptor instanceof Autowire => $interceptor->resolve($factory),
                default => $interceptor,
            };
        }

        // Create pipeline builder with common interceptors
        /** @var InterceptorInterface[] $list */
        $pipelineBuilder = $pipelineBuilder->withInterceptors(...$list);

        $this->builder = new Builder($registry, $pipelineBuilder);
    }

    /**
     * Use this method to define services that should be available in the container.
     *
     * @return array<class-string>
     */
    public function getClientInterfaces(): array
    {
        $result = [];
        foreach ($this->config->services as $service) {
            foreach ($service->interfaces as $interface) {
                $result[] = $interface;
            }
        }

        return \array_unique($result);
    }

    /**
     * @template T
     * @param class-string<T> $service
     * @return T
     */
    public function getServiceClient(string $service): object
    {
        return $this->builder->build($service);
    }
}
