<?php

declare(strict_types=1);

namespace Spiral\Grpc\Client\Interceptor;

use Spiral\Core\Attribute\Proxy;
use Spiral\Core\Container\Autowire;
use Spiral\Core\FactoryInterface;
use Spiral\Grpc\Client\Internal\Registry\ServiceRegistry;
use Spiral\Interceptors\Context\CallContextInterface;
use Spiral\Interceptors\HandlerInterface;
use Spiral\Interceptors\InterceptorInterface as Interceptor;
use Spiral\Interceptors\PipelineBuilderInterface;

/**
 * Execute an interceptor pipeline for the current service.
 */
final class ExecuteServiceInterceptors implements Interceptor
{
    public function __construct(
        #[Proxy] private readonly FactoryInterface $factory,
        private readonly ServiceRegistry $serviceRegistry,
        private readonly PipelineBuilderInterface $pipelineBuilder,
    ) {}

    public function intercept(CallContextInterface $context, HandlerInterface $handler): mixed
    {
        // Get current connection
        $connection = Helper::getCurrentConnection($context);

        // Find the interceptor pipeline for the service
        $interceptors = $this->serviceRegistry->getServiceConfigByConnection($connection)?->interceptors ?? [];

        return $interceptors === []
            ? $handler->handle($context)
            : $this->pipelineBuilder
                ->withInterceptors(...$this->autowire($interceptors))
                ->build($handler)
                ->handle($context);
    }

    /**
     * Autowire the interceptors.
     *
     * @param iterable<Interceptor|class-string<Interceptor>|Autowire> $interceptors
     * @return \Traversable<Interceptor>
     */
    private function autowire(iterable $interceptors): \Traversable
    {
        foreach ($interceptors as $interceptor) {
            yield match (true) {
                \is_string($interceptor) => $this->factory->make($interceptor),
                $interceptor instanceof Autowire => $interceptor->resolve($this->factory),
                default => $interceptor,
            };
        }
    }
}
