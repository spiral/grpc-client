<?php

declare(strict_types=1);

namespace Spiral\Grpc\Client\Tests\Unit\Fixture;

use Spiral\Interceptors\HandlerInterface;
use Spiral\Interceptors\InterceptorInterface;
use Spiral\Interceptors\PipelineBuilder;
use Spiral\Interceptors\PipelineBuilderInterface;

/**
 * Builds pipelines like {@see PipelineBuilder} and records the interceptors it was given.
 */
final class SpyPipelineBuilder implements PipelineBuilderInterface
{
    /**
     * Interceptor lists passed to {@see withInterceptors()} of this instance; the returned copies record their own.
     *
     * @var list<list<InterceptorInterface>>
     */
    public array $calls = [];

    private PipelineBuilderInterface $inner;

    public function __construct()
    {
        $this->inner = new PipelineBuilder();
    }

    public function withInterceptors(InterceptorInterface ...$interceptors): static
    {
        $this->calls[] = \array_values($interceptors);
        $clone = clone $this;
        $clone->calls = [];
        $clone->inner = $this->inner->withInterceptors(...$interceptors);
        return $clone;
    }

    public function build(HandlerInterface $handler): HandlerInterface
    {
        return $this->inner->build($handler);
    }
}
