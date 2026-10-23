<?php

declare(strict_types=1);

namespace Spiral\Grpc\Client\Interceptor;

use Spiral\Core\Attribute\Proxy;
use Spiral\Core\Container\Autowire;
use Spiral\Core\FactoryInterface;
use Spiral\Interceptors\Context\CallContextInterface;
use Spiral\Interceptors\HandlerInterface;
use Spiral\Interceptors\InterceptorInterface as Interceptor;
use Spiral\Interceptors\PipelineBuilder;
use Spiral\Interceptors\PipelineBuilderInterface;

/**
 * Execute the interceptors of the service that owns the current connection.
 *
 * The service is taken from the call context, see {@see Helper::getServiceConfig()}.
 */
final class ExecuteServiceInterceptors implements Interceptor
{
    public function __construct(
        #[Proxy] private readonly FactoryInterface $factory,
        private readonly PipelineBuilderInterface $pipelineBuilder = new PipelineBuilder(),
    ) {}

    #[\Override]
    public function intercept(CallContextInterface $context, HandlerInterface $handler): mixed
    {
        $interceptors = Helper::getServiceConfig($context)?->interceptors ?? [];

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
     * @param iterable<Interceptor|class-string<Interceptor>|Autowire<Interceptor>> $interceptors
     * @return \Generator<int, Interceptor, mixed, void>
     */
    private function autowire(iterable $interceptors): \Traversable
    {
        foreach ($interceptors as $interceptor) {
            yield match (true) {
                \is_string($interceptor) => $this->factory->make($interceptor),
                $interceptor instanceof Interceptor => $interceptor,
                default => $interceptor->resolve($this->factory),
            };
        }
    }
}
