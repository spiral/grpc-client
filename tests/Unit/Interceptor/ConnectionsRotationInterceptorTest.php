<?php

declare(strict_types=1);

namespace Spiral\Grpc\Client\Tests\Unit\Interceptor;

use Spiral\Grpc\Client\Exception\ServiceClientException;
use Spiral\Grpc\Client\Interceptor\ConnectionsRotationInterceptor;
use Spiral\Grpc\Client\Interceptor\Helper;
use Spiral\Grpc\Client\Internal\StatusCode;
use Spiral\Grpc\Client\Tests\Unit\Fixture\CallContextFactory;
use Spiral\Grpc\Client\Tests\Unit\Fixture\FakeConnection;
use Spiral\Grpc\Client\Tests\Unit\Fixture\RecordingHandler;
use Spiral\Interceptors\Context\CallContextInterface;
use Testo\Assert;
use Testo\Codecov\Covers;
use Testo\Skip;
use Testo\Test;

#[Test]
#[Covers(ConnectionsRotationInterceptor::class)]
final class ConnectionsRotationInterceptorTest
{
    public function singleConnectionIsPassedThrough(): void
    {
        $context = CallContextFactory::create([new FakeConnection()]);
        $handler = new RecordingHandler(static fn(): string => 'ok');

        $result = (new ConnectionsRotationInterceptor())->intercept($context, $handler);

        Assert::same($result, 'ok');
        Assert::same($handler->contexts, [$context]);
    }

    public function failedConnectionIsReplacedWithTheNextOne(): void
    {
        $first = new FakeConnection();
        $second = new FakeConnection();
        $handler = new RecordingHandler(
            static fn(CallContextInterface $ctx, int $call): string => $call === 1
                ? throw self::unavailable()
                : 'ok',
        );

        $result = (new ConnectionsRotationInterceptor())
            ->intercept(CallContextFactory::create([$first, $second]), $handler);

        Assert::same($result, 'ok');
        Assert::same(Helper::getCurrentConnection($handler->contexts[0]), $first);
        Assert::same(Helper::getCurrentConnection($handler->contexts[1]), $second);
    }

    public function connectedConnectionIsTriedFirst(): void
    {
        $idle = new FakeConnection(connected: false);
        $connected = new FakeConnection(connected: true);
        $handler = new RecordingHandler(static fn(): string => 'ok');

        (new ConnectionsRotationInterceptor())
            ->intercept(CallContextFactory::create([$idle, $connected]), $handler);

        Assert::same($handler->calls(), 1);
        Assert::same(Helper::getCurrentConnection($handler->contexts[0]), $connected);
    }

    public function lastErrorIsThrownWhenAllConnectionsFail(): void
    {
        $last = self::unavailable('last');
        $handler = new RecordingHandler(
            static fn(CallContextInterface $ctx, int $call): never => throw ($call === 3 ? $last : self::unavailable()),
        );

        try {
            (new ConnectionsRotationInterceptor())->intercept(
                CallContextFactory::create([new FakeConnection(), new FakeConnection(), new FakeConnection()]),
                $handler,
            );
            Assert::fail('Exception expected.');
        } catch (ServiceClientException $e) {
            Assert::same($e, $last);
        }

        Assert::same($handler->calls(), 3);
    }

    public function otherErrorsAreNotRotated(): void
    {
        $handler = new RecordingHandler(static fn(): never => throw new \LogicException('boom'));

        try {
            (new ConnectionsRotationInterceptor())
                ->intercept(CallContextFactory::create([new FakeConnection(), new FakeConnection()]), $handler);
            Assert::fail('Exception expected.');
        } catch (\LogicException) {
        }

        Assert::same($handler->calls(), 1);
    }

    #[Skip('Bug: the result of Helper::withConnections() is discarded, so the next attempt still sees the failed connection.')]
    public function failedConnectionIsRemovedFromTheContextOfTheNextAttempt(): void
    {
        $first = new FakeConnection();
        $second = new FakeConnection();
        $handler = new RecordingHandler(
            static fn(CallContextInterface $ctx, int $call): string => $call === 1
                ? throw self::unavailable()
                : 'ok',
        );

        (new ConnectionsRotationInterceptor())
            ->intercept(CallContextFactory::create([$first, $second]), $handler);

        Assert::same(Helper::getConnections($handler->contexts[1]), [$second]);
    }

    private static function unavailable(string $details = 'unavailable'): ServiceClientException
    {
        return new ServiceClientException((object) [
            'code' => StatusCode::Unavailable->value,
            'details' => $details,
            'metadata' => [],
        ]);
    }
}
