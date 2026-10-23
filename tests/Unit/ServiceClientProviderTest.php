<?php

declare(strict_types=1);

namespace Spiral\Grpc\Client\Tests\Unit;

use Google\Protobuf\GPBEmpty;
use Spiral\Core\Container;
use Spiral\Core\Container\Autowire;
use Spiral\Grpc\Client\Config\ConnectionConfig;
use Spiral\Grpc\Client\Config\GrpcClientConfig;
use Spiral\Grpc\Client\Config\ServiceConfig;
use Spiral\Grpc\Client\Interceptor\ExecuteServiceInterceptors;
use Spiral\Grpc\Client\Interceptor\Helper;
use Spiral\Grpc\Client\ServiceClientProvider;
use Spiral\Grpc\Client\Tests\Acceptance\GrpcContext;
use Spiral\Grpc\Client\Tests\Unit\Fixture\OtherServiceInterface;
use Spiral\Grpc\Client\Tests\Unit\Fixture\RecordingInterceptor;
use Spiral\Grpc\Client\Tests\Unit\Fixture\ShortCircuitInterceptor;
use Spiral\Grpc\Client\Tests\Unit\Fixture\SpyPipelineBuilder;
use Spiral\Grpc\Client\Tests\Unit\Fixture\TestServiceInterface;
use Spiral\Interceptors\PipelineBuilder;
use Spiral\Interceptors\PipelineBuilderInterface;
use Testo\Assert;
use Testo\Codecov\Covers;
use Testo\Data\DataProvider;
use Testo\Test;

#[Test]
#[Covers(ServiceClientProvider::class)]
final class ServiceClientProviderTest
{
    public static function provideExecuteServiceInterceptorsDefinitions(): iterable
    {
        yield 'class name' => [static fn(): string => ExecuteServiceInterceptors::class];
        yield 'class name with a leading backslash' => [static fn(): string => '\\' . ExecuteServiceInterceptors::class];
        yield 'autowire' => [static fn(): Autowire => new Autowire(ExecuteServiceInterceptors::class)];
        yield 'autowire with a pipeline builder' => [
            static fn(): Autowire => new Autowire(
                ExecuteServiceInterceptors::class,
                ['pipelineBuilder' => new PipelineBuilder()],
            ),
        ];
        yield 'instance' => [static fn(): ExecuteServiceInterceptors => new ExecuteServiceInterceptors(new Container())];
        yield 'container alias' => [
            static function (Container $container): string {
                $container->bindSingleton('grpc.service-interceptors', ExecuteServiceInterceptors::class);
                return 'grpc.service-interceptors';
            },
        ];
    }

    /**
     * @param \Closure(Container): (string|Autowire|ExecuteServiceInterceptors) $definition
     */
    #[DataProvider('provideExecuteServiceInterceptorsDefinitions')]
    public function executeServiceInterceptorsRunsServiceInterceptors(\Closure $definition): void
    {
        $container = new Container();
        $serviceInterceptor = new ShortCircuitInterceptor();
        $provider = new ServiceClientProvider(
            new GrpcClientConfig(
                interceptors: [$definition($container)],
                services: [
                    new ServiceConfig(
                        new ConnectionConfig('127.0.0.1:1'),
                        interceptors: [$serviceInterceptor],
                        interfaces: [TestServiceInterface::class],
                    ),
                ],
            ),
            $container,
        );

        $response = $provider->getServiceClient(TestServiceInterface::class)->Ping(new GrpcContext(), new GPBEmpty());

        Assert::instanceOf($response, GPBEmpty::class);
        Assert::count($serviceInterceptor->contexts, 1);
    }

    public function containerPipelineBuilderRunsServiceInterceptors(): void
    {
        $builder = new SpyPipelineBuilder();
        $container = new Container();
        $container->bindSingleton(PipelineBuilderInterface::class, $builder);
        $serviceInterceptor = new ShortCircuitInterceptor();
        $provider = new ServiceClientProvider(
            new GrpcClientConfig(
                interceptors: [ExecuteServiceInterceptors::class],
                services: [new ServiceConfig(
                    new ConnectionConfig('127.0.0.1:1'),
                    interceptors: [$serviceInterceptor],
                    interfaces: [TestServiceInterface::class],
                )],
            ),
            $container,
        );

        $provider->getServiceClient(TestServiceInterface::class)->Ping(new GrpcContext(), new GPBEmpty());

        Assert::same($builder->calls, [[$serviceInterceptor]]);
        Assert::count($serviceInterceptor->contexts, 1);
    }

    public function servicesSharingAConnectionRunTheirOwnInterceptors(): void
    {
        $shared = new ConnectionConfig('127.0.0.1:1');
        $testInterceptor = new ShortCircuitInterceptor();
        $otherInterceptor = new ShortCircuitInterceptor();
        $provider = self::provider(new GrpcClientConfig(
            interceptors: [ExecuteServiceInterceptors::class],
            services: [
                new ServiceConfig($shared, [$otherInterceptor], [OtherServiceInterface::class]),
                new ServiceConfig($shared, [$testInterceptor], [TestServiceInterface::class]),
            ],
        ));

        $provider->getServiceClient(TestServiceInterface::class)->Ping(new GrpcContext(), new GPBEmpty());

        Assert::count($testInterceptor->contexts, 1);
        Assert::count($otherInterceptor->contexts, 0);
    }

    public function clientInterfacesAreCollectedFromAllServicesWithoutDuplicates(): void
    {
        $connection = new ConnectionConfig('127.0.0.1:1');
        $provider = self::provider(new GrpcClientConfig(services: [
            new ServiceConfig($connection, interfaces: [TestServiceInterface::class, OtherServiceInterface::class]),
            new ServiceConfig($connection, interfaces: [TestServiceInterface::class]),
        ]));

        Assert::same(
            \array_values($provider->getClientInterfaces()),
            [TestServiceInterface::class, OtherServiceInterface::class],
        );
    }

    public function noServicesMeansNoInterfaces(): void
    {
        Assert::same(self::provider(new GrpcClientConfig())->getClientInterfaces(), []);
    }

    public function serviceClientRunsCommonInterceptorsInOrder(): void
    {
        RecordingInterceptor::$log = [];
        $terminal = new ShortCircuitInterceptor();
        $connection = new ConnectionConfig('127.0.0.1:1');
        $provider = self::provider(new GrpcClientConfig(
            interceptors: [
                RecordingInterceptor::class,
                new Autowire(RecordingInterceptor::class, ['name' => 'autowired']),
                $terminal,
            ],
            services: [new ServiceConfig($connection, interfaces: [TestServiceInterface::class])],
        ));

        $client = $provider->getServiceClient(TestServiceInterface::class);
        $response = $client->Ping(new GrpcContext(), new GPBEmpty());

        Assert::instanceOf($client, TestServiceInterface::class);
        Assert::instanceOf($response, GPBEmpty::class);
        Assert::same(RecordingInterceptor::$log, ['default', 'autowired']);
        Assert::count($terminal->contexts, 1);
        Assert::same($terminal->contexts[0]->getArguments()[1], '/test.Service/Ping');
        Assert::count(Helper::getConnections($terminal->contexts[0]), 1);
    }

    private static function provider(GrpcClientConfig $config): ServiceClientProvider
    {
        return new ServiceClientProvider($config, new Container());
    }
}
