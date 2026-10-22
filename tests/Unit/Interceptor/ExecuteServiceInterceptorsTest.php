<?php

declare(strict_types=1);

namespace Spiral\Grpc\Client\Tests\Unit\Interceptor;

use Spiral\Core\Container;
use Spiral\Core\Container\Autowire;
use Spiral\Grpc\Client\Config\ConnectionConfig;
use Spiral\Grpc\Client\Config\ServiceConfig;
use Spiral\Grpc\Client\Interceptor\ExecuteServiceInterceptors;
use Spiral\Grpc\Client\Internal\Connection\ConnectionInterface;
use Spiral\Grpc\Client\Internal\Registry\ServiceRegistry;
use Spiral\Grpc\Client\Tests\Unit\Fixture\CallContextFactory;
use Spiral\Grpc\Client\Tests\Unit\Fixture\FakeConnection;
use Spiral\Grpc\Client\Tests\Unit\Fixture\RecordingHandler;
use Spiral\Grpc\Client\Tests\Unit\Fixture\RecordingInterceptor;
use Spiral\Interceptors\PipelineBuilder;
use Testo\Assert;
use Testo\Codecov\Covers;
use Testo\Test;

#[Test]
#[Covers(ExecuteServiceInterceptors::class)]
final class ExecuteServiceInterceptorsTest
{
    public function unknownConnectionGoesStraightToHandler(): void
    {
        $context = CallContextFactory::create([new FakeConnection()]);
        $handler = new RecordingHandler(static fn(): string => 'ok');

        $result = self::create(new ServiceRegistry())->intercept($context, $handler);

        Assert::same($result, 'ok');
        Assert::same($handler->contexts, [$context]);
    }

    public function serviceWithoutInterceptorsGoesStraightToHandler(): void
    {
        [$registry, $connection] = self::registryWithService([]);
        $handler = new RecordingHandler(static fn(): string => 'ok');

        $result = self::create($registry)->intercept(CallContextFactory::create([$connection]), $handler);

        Assert::same($result, 'ok');
        Assert::same($handler->calls(), 1);
    }

    public function serviceInterceptorsRunInOrderBeforeHandler(): void
    {
        RecordingInterceptor::$log = [];
        [$registry, $connection] = self::registryWithService([
            RecordingInterceptor::class,
            new Autowire(RecordingInterceptor::class, ['name' => 'autowired']),
            new RecordingInterceptor('instance'),
        ]);
        $handler = new RecordingHandler(static function (): string {
            RecordingInterceptor::$log[] = 'handler';
            return 'ok';
        });

        $result = self::create($registry)->intercept(CallContextFactory::create([$connection]), $handler);

        Assert::same($result, 'ok');
        Assert::same(RecordingInterceptor::$log, ['default', 'autowired', 'instance', 'handler']);
    }

    private static function create(ServiceRegistry $registry): ExecuteServiceInterceptors
    {
        return new ExecuteServiceInterceptors(new Container(), $registry, new PipelineBuilder());
    }

    /**
     * @return array{ServiceRegistry, ConnectionInterface}
     */
    private static function registryWithService(array $interceptors): array
    {
        $connectionConfig = new ConnectionConfig('127.0.0.1:1');
        $registry = new ServiceRegistry();
        $registry->addServices(new ServiceConfig($connectionConfig, $interceptors));

        return [$registry, $registry->getConnection($connectionConfig)];
    }
}
