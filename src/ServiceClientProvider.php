<?php

declare(strict_types=1);

namespace Spiral\Grpc\Client;

use Spiral\Core\Attribute\Proxy;
use Spiral\Core\Attribute\Singleton;
use Spiral\Core\Container\Autowire;
use Spiral\Core\FactoryInterface;
use Spiral\Grpc\Client\Config\GrpcClientConfig;
use Spiral\Grpc\Client\Internal\Registry\ServiceRegistry;
use Spiral\Grpc\Client\Internal\ServiceClient\ClientFactory;
use Spiral\Interceptors\Handler\CallableHandler;
use Spiral\Interceptors\InterceptorInterface;
use Spiral\Interceptors\PipelineBuilder;
use Spiral\Interceptors\PipelineBuilderInterface;

#[Singleton]
final class ServiceClientProvider
{
    private readonly ClientFactory $builder;

    public function __construct(
        private readonly GrpcClientConfig $config,
        #[Proxy] private readonly FactoryInterface $factory,
        private readonly PipelineBuilderInterface $pipelineBuilder = new PipelineBuilder(),
    ) {

        // Collect all the services
        $registry = new ServiceRegistry();
        $registry->addServices(...$this->config->services);

        $this->builder = new ClientFactory($registry);
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
        $handler = $this->makePipeline()->build(new CallableHandler());
        return $this->builder->make($service, $handler);
    }

    /**
     * Create an interceptor pipeline builder with common interceptors
     */
    private function makePipeline(): PipelineBuilderInterface
    {
        // Prepare common interceptors
        /** @var InterceptorInterface[] $list */
        $list = [];
        foreach ($this->config->interceptors as $interceptor) {
            $list[] = match (true) {
                \is_string($interceptor) => $this->factory->make($interceptor),
                $interceptor instanceof Autowire => $interceptor->resolve($this->factory),
                default => $interceptor,
            };
        }

        return $this->pipelineBuilder->withInterceptors(...$list);
    }
}
