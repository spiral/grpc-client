<?php

declare(strict_types=1);

namespace Spiral\Grpc\Client\Tests\Unit\Interceptor;

use Spiral\Core\Container;
use Spiral\Core\Container\Autowire;
use Spiral\Grpc\Client\Config\ConnectionConfig;
use Spiral\Grpc\Client\Config\ServiceConfig;
use Spiral\Grpc\Client\Exception\ServiceClientException;
use Spiral\Grpc\Client\Interceptor\ConnectionsRotationInterceptor;
use Spiral\Grpc\Client\Interceptor\ExecuteServiceInterceptors;
use Spiral\Grpc\Client\Interceptor\RetryInterceptor;
use Spiral\Grpc\Client\Interceptor\RetryInterceptor\RetryOptions;
use Spiral\Grpc\Client\Internal\StatusCode;
use Spiral\Grpc\Client\Tests\Unit\Fixture\CallContextFactory;
use Spiral\Grpc\Client\Tests\Unit\Fixture\FakeConnection;
use Spiral\Grpc\Client\Tests\Unit\Fixture\RecordingHandler;
use Spiral\Grpc\Client\Tests\Unit\Fixture\RecordingInterceptor;
use Spiral\Interceptors\Context\CallContextInterface;
use Spiral\Interceptors\PipelineBuilder;
use Testo\Assert;
use Testo\Codecov\Covers;
use Testo\Test;

#[Test]
#[Covers(ExecuteServiceInterceptors::class)]
final class ExecuteServiceInterceptorsTest
{
    public function callWithoutServiceConfigsGoesStraightToHandler(): void
    {
        $context = CallContextFactory::create();
        $handler = new RecordingHandler(static fn(): string => 'ok');

        $result = self::create()->intercept($context, $handler);

        Assert::same($result, 'ok');
        Assert::same($handler->contexts, [$context]);
    }

    public function connectionWithoutServiceGoesStraightToHandler(): void
    {
        RecordingInterceptor::$log = [];
        $other = new FakeConnection();
        $context = CallContextFactory::create(
            [new FakeConnection(), $other],
            serviceConfigs: CallContextFactory::serviceConfigs([$other, self::service([RecordingInterceptor::class])]),
        );
        $handler = new RecordingHandler(static fn(): string => 'ok');

        $result = self::create()->intercept($context, $handler);

        Assert::same($result, 'ok');
        Assert::same($handler->contexts, [$context]);
        Assert::same(RecordingInterceptor::$log, []);
    }

    public function serviceWithoutInterceptorsGoesStraightToHandler(): void
    {
        $connection = new FakeConnection();
        $context = CallContextFactory::create(
            [$connection],
            serviceConfigs: CallContextFactory::serviceConfigs([$connection, self::service([])]),
        );
        $handler = new RecordingHandler(static fn(): string => 'ok');

        $result = self::create()->intercept($context, $handler);

        Assert::same($result, 'ok');
        Assert::same($handler->contexts, [$context]);
    }

    public function serviceInterceptorsRunInOrderBeforeHandler(): void
    {
        RecordingInterceptor::$log = [];
        $connection = new FakeConnection();
        $context = CallContextFactory::create([$connection], serviceConfigs: CallContextFactory::serviceConfigs(
            [$connection, self::service([
                RecordingInterceptor::class,
                new Autowire(RecordingInterceptor::class, ['name' => 'autowired']),
                new RecordingInterceptor('instance'),
            ])],
        ));
        $handler = new RecordingHandler(static function (): string {
            RecordingInterceptor::$log[] = 'handler';
            return 'ok';
        });

        $result = self::create()->intercept($context, $handler);

        Assert::same($result, 'ok');
        Assert::same(RecordingInterceptor::$log, ['default', 'autowired', 'instance', 'handler']);
        Assert::same($handler->contexts, [$context]);
    }

    public function interceptorsOfTheCurrentConnectionServiceRun(): void
    {
        RecordingInterceptor::$log = [];
        [$first, $second] = [new FakeConnection(), new FakeConnection()];
        $context = CallContextFactory::create([$second, $first], serviceConfigs: CallContextFactory::serviceConfigs(
            [$first, self::service([new RecordingInterceptor('first')])],
            [$second, self::service([new RecordingInterceptor('second')])],
        ));

        self::create()->intercept($context, new RecordingHandler(static fn(): string => 'ok'));

        Assert::same(RecordingInterceptor::$log, ['second']);
    }

    public function failoverRunsInterceptorsOfTheNextConnectionService(): void
    {
        RecordingInterceptor::$log = [];
        [$first, $second] = [new FakeConnection(), new FakeConnection()];
        $context = CallContextFactory::create([$first, $second], serviceConfigs: CallContextFactory::serviceConfigs(
            [$first, self::service([new RecordingInterceptor('first')])],
            [$second, self::service([new RecordingInterceptor('second')])],
        ));
        $handler = new RecordingHandler(
            static fn(CallContextInterface $ctx, int $call): string => $call === 1
                ? throw new ServiceClientException((object) [
                    'code' => StatusCode::Unavailable->value,
                    'details' => 'unavailable',
                    'metadata' => [],
                ])
                : 'ok',
        );
        $pipeline = (new PipelineBuilder())
            ->withInterceptors(new ConnectionsRotationInterceptor(), self::create())
            ->build($handler);

        $result = $pipeline->handle($context);

        Assert::same($result, 'ok');
        Assert::same(RecordingInterceptor::$log, ['first', 'second']);
    }

    public function retryRunsServiceInterceptorsOfEachAttemptedConnection(): void
    {
        RecordingInterceptor::$log = [];
        [$first, $second] = [new FakeConnection(), new FakeConnection()];
        $context = CallContextFactory::create([$first, $second], serviceConfigs: CallContextFactory::serviceConfigs(
            [$first, self::service([new RecordingInterceptor('first')])],
            [$second, self::service([new RecordingInterceptor('second')])],
        ));
        // Both connections fail once, so the retry starts the rotation over
        $handler = new RecordingHandler(
            static fn(CallContextInterface $ctx, int $call): string => $call <= 2
                ? throw new ServiceClientException((object) [
                    'code' => StatusCode::Unavailable->value,
                    'details' => 'unavailable',
                    'metadata' => [],
                ])
                : 'ok',
        );
        $pipeline = (new PipelineBuilder())
            ->withInterceptors(
                new RetryInterceptor((new RetryOptions())->withInitialInterval(0)->withMaximumAttempts(2)),
                new ConnectionsRotationInterceptor(),
                self::create(),
            )
            ->build($handler);

        $result = $pipeline->handle($context);

        Assert::same($result, 'ok');
        Assert::same(RecordingInterceptor::$log, ['first', 'second', 'first']);
    }

    private static function create(): ExecuteServiceInterceptors
    {
        return new ExecuteServiceInterceptors(new Container());
    }

    private static function service(array $interceptors): ServiceConfig
    {
        return new ServiceConfig(new ConnectionConfig('127.0.0.1:1'), $interceptors);
    }
}
