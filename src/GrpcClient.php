<?php

declare(strict_types=1);

namespace Spiral\Grpc\Client;

use Spiral\Core\Container;
use Spiral\Core\Container\Autowire;
use Spiral\Core\FactoryInterface;
use Spiral\Grpc\Client\Config\ConnectionConfig;
use Spiral\Grpc\Client\Config\ServiceConfig;
use Spiral\Grpc\Client\Internal\Registry\ServiceRegistry;
use Spiral\Grpc\Client\Internal\ServiceClient\ClientFactory;
use Spiral\Interceptors\Handler\CallableHandler;
use Spiral\Interceptors\HandlerInterface;
use Spiral\Interceptors\InterceptorInterface;
use Spiral\Interceptors\PipelineBuilder;
use Spiral\Interceptors\PipelineBuilderInterface;

/**
 * Standalone gRPC client that doesn't require a DI container or framework integration.
 *
 * One instance represents a single set of endpoints. Use {@see self::service()} to obtain
 * typed service clients and {@see self::withInterceptors()} to configure the interceptor pipeline.
 *
 * For per-service interceptors or full framework integration,
 * use {@see ServiceClientProvider} with {@see Config\GrpcClientConfig} instead.
 */
final class GrpcClient
{
    /** @var list<ConnectionConfig> */
    private readonly array $connections;

    /** @var list<InterceptorInterface|class-string<InterceptorInterface>|Autowire> */
    private array $interceptors = [];

    private ?FactoryInterface $factory = null;
    private PipelineBuilderInterface $pipelineBuilder;
    private ?ServiceRegistry $registry = null;
    private ?ClientFactory $clientFactory = null;
    private ?HandlerInterface $handler = null;

    /** @var array<class-string, object> */
    private array $serviceCache = [];

    /**
     * @param list<ConnectionConfig> $connections
     */
    private function __construct(array $connections)
    {
        $this->connections = $connections;
        $this->pipelineBuilder = new PipelineBuilder();
    }

    /**
     * Create a new gRPC client for the given endpoint(s).
     *
     * @param non-empty-string|ConnectionConfig|list<non-empty-string|ConnectionConfig> $endpoints
     *        At least one endpoint is required. Strings are auto-wrapped in {@see ConnectionConfig}.
     */
    public static function create(string|ConnectionConfig|array $endpoints): self
    {
        $endpoints = \is_array($endpoints) ? $endpoints : [$endpoints];
        $endpoints === [] and throw new \InvalidArgumentException(
            'At least one endpoint is required.',
        );

        $connections = [];
        foreach ($endpoints as $endpoint) {
            $connections[] = \is_string($endpoint)
                ? new ConnectionConfig($endpoint)
                : $endpoint;
        }

        return new self($connections);
    }

    /**
     * Return a new instance with the given interceptors (replaces, not appends).
     *
     * @param list<InterceptorInterface|class-string<InterceptorInterface>|Autowire> $interceptors
     */
    public function withInterceptors(array $interceptors): self
    {
        $clone = clone $this;
        $clone->interceptors = $interceptors;
        $clone->resetLazyState();
        return $clone;
    }

    /**
     * Return a new instance with the given factory for DI-based interceptor resolution.
     */
    public function withFactory(FactoryInterface $factory): self
    {
        $clone = clone $this;
        $clone->factory = $factory;
        $clone->resetLazyState();
        return $clone;
    }

    /**
     * Return a new instance with the given pipeline builder.
     */
    public function withPipelineBuilder(PipelineBuilderInterface $pipelineBuilder): self
    {
        $clone = clone $this;
        $clone->pipelineBuilder = $pipelineBuilder;
        $clone->resetLazyState();
        return $clone;
    }

    /**
     * Get a typed gRPC service client for the given interface.
     *
     * @template T
     * @param class-string<T> $interface
     * @return T
     */
    public function service(string $interface): object
    {
        if (isset($this->serviceCache[$interface])) {
            /** @var T */
            return $this->serviceCache[$interface];
        }

        $this->registry ??= new ServiceRegistry();
        $this->clientFactory ??= new ClientFactory($this->registry);

        $this->registry->addServices(new ServiceConfig(
            connections: $this->connections,
            interfaces: [$interface],
        ));

        $this->handler ??= $this->buildHandler();

        $client = $this->clientFactory->make($interface, $this->handler);
        $this->serviceCache[$interface] = $client;

        /** @var T */
        return $client;
    }

    private function buildHandler(): HandlerInterface
    {
        $factory = $this->factory ??= new Container();

        $resolved = [];
        foreach ($this->interceptors as $interceptor) {
            $resolved[] = $this->resolveInterceptor($interceptor, $factory);
        }

        return $this->pipelineBuilder
            ->withInterceptors(...$resolved)
            ->build(new CallableHandler());
    }

    /**
     * @param InterceptorInterface|class-string<InterceptorInterface>|Autowire $interceptor
     */
    private function resolveInterceptor(
        InterceptorInterface|string|Autowire $interceptor,
        FactoryInterface $factory,
    ): InterceptorInterface {
        if ($interceptor instanceof InterceptorInterface) {
            return $interceptor;
        }

        if ($interceptor instanceof Autowire) {
            /** @var InterceptorInterface */
            return $interceptor->resolve($factory);
        }

        /** @var InterceptorInterface */
        return $factory->make($interceptor);
    }

    private function resetLazyState(): void
    {
        $this->registry = null;
        $this->clientFactory = null;
        $this->handler = null;
        $this->serviceCache = [];
    }
}
