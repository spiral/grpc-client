<?php

declare(strict_types=1);

namespace Spiral\Grpc\Client\Tests\Unit\Interceptor;

use Spiral\Core\Container;
use Spiral\Grpc\Client\Exception\ServiceClientException;
use Spiral\Grpc\Client\Exception\TimeoutException;
use Spiral\Grpc\Client\Interceptor\RetryInterceptor;
use Spiral\Grpc\Client\Interceptor\RetryInterceptor\RetryOptions;
use Spiral\Grpc\Client\Internal\StatusCode;
use Spiral\Grpc\Client\Tests\Unit\Fixture\CallContextFactory;
use Spiral\Grpc\Client\Tests\Unit\Fixture\RecordingHandler;
use Spiral\Interceptors\Context\CallContextInterface;
use Testo\Assert;
use Testo\Codecov\Covers;
use Testo\Data\DataProvider;
use Testo\Test;

#[Test]
#[Covers(RetryInterceptor::class)]
final class RetryInterceptorTest
{
    public static function provideRetryableCodes(): iterable
    {
        foreach (RetryInterceptor::RETRYABLE_ERRORS as $code) {
            yield $code->name => [$code];
        }
    }

    public static function provideNonRetryableCodes(): iterable
    {
        yield 'NotFound' => [StatusCode::NotFound->value];
        yield 'InvalidArgument' => [StatusCode::InvalidArgument->value];
        yield 'code outside of the enum' => [999];
    }

    #[DataProvider('provideRetryableCodes')]
    public function retryableErrorIsRetriedUntilSuccess(StatusCode $code): void
    {
        $handler = self::failingHandler(times: 2, code: $code->value);

        $result = self::interceptor(maximumAttempts: 5)->intercept(CallContextFactory::create(), $handler);

        Assert::same($result, 'ok');
        Assert::same($handler->calls(), 3);
    }

    #[DataProvider('provideNonRetryableCodes')]
    public function nonRetryableErrorIsRethrownImmediately(int $code): void
    {
        $handler = self::failingHandler(times: 1, code: $code);

        $e = self::catch(static fn() => self::interceptor()->intercept(CallContextFactory::create(), $handler));

        Assert::instanceOf($e, ServiceClientException::class);
        Assert::same($e->getCode(), $code);
        Assert::same($handler->calls(), 1);
    }

    public function deadlineExceededIsConvertedToTimeoutException(): void
    {
        $handler = self::failingHandler(times: 1, code: StatusCode::DeadlineExceeded->value);

        $e = self::catch(static fn() => self::interceptor()->intercept(CallContextFactory::create(), $handler));

        Assert::instanceOf($e, TimeoutException::class);
        Assert::same($e->getCode(), StatusCode::DeadlineExceeded->value);
        Assert::instanceOf($e->getPrevious(), ServiceClientException::class);
    }

    public function lastErrorIsThrownWhenAttemptsAreExhausted(): void
    {
        $handler = self::failingHandler(times: 10, code: StatusCode::Unavailable->value);

        $e = self::catch(
            static fn() => self::interceptor(maximumAttempts: 3)->intercept(CallContextFactory::create(), $handler),
        );

        Assert::instanceOf($e, ServiceClientException::class);
        Assert::same($handler->calls(), 3);
    }

    public function retryOptionsFromContextOverrideDefaults(): void
    {
        $handler = self::failingHandler(times: 10, code: StatusCode::Unavailable->value);
        $context = CallContextFactory::create()
            ->withAttribute(RetryOptions::class, (new RetryOptions())->withMaximumAttempts(1));

        self::catch(static fn() => self::interceptor(maximumAttempts: 5)->intercept($context, $handler));

        Assert::same($handler->calls(), 1);
    }

    public function retryThatWouldMissTheDeadlineFailsWithTimeout(): void
    {
        $handler = self::failingHandler(times: 10, code: StatusCode::Unavailable->value);
        // A 1 ms deadline against a 10 s backoff: the retry cannot start in time.
        $context = CallContextFactory::create(options: ['timeout' => 1_000]);
        $interceptor = new RetryInterceptor(
            (new RetryOptions())->withInitialInterval(10_000)->withMaximumJitterCoefficient(0.0),
        );

        $e = self::catch(static fn() => $interceptor->intercept($context, $handler));

        Assert::instanceOf($e, TimeoutException::class);
        Assert::same($e->getCode(), StatusCode::DeadlineExceeded->value);
        Assert::same($handler->calls(), 1);
    }

    public function retryInsideFiberSuspendsInsteadOfBlocking(): void
    {
        $handler = self::failingHandler(times: 1, code: StatusCode::Unavailable->value);
        $interceptor = new RetryInterceptor(
            (new RetryOptions())->withInitialInterval(20)->withMaximumJitterCoefficient(0.0)->withMaximumAttempts(2),
        );
        $fiber = new \Fiber(static fn(): mixed => $interceptor->intercept(CallContextFactory::create(), $handler));

        $suspensions = 0;
        $fiber->start();
        while (!$fiber->isTerminated()) {
            ++$suspensions;
            $fiber->resume();
        }

        Assert::same($fiber->getReturn(), 'ok');
        Assert::true($suspensions > 0);
        Assert::same($handler->calls(), 2);
    }

    public function createConfigResolvesToConfiguredInterceptor(): void
    {
        $handler = self::failingHandler(times: 10, code: StatusCode::Unavailable->value);
        $interceptor = RetryInterceptor::createConfig(initialInterval: 1, maximumAttempts: 2)
            ->resolve(new Container());

        Assert::instanceOf($interceptor, RetryInterceptor::class);
        self::catch(static fn() => $interceptor->intercept(CallContextFactory::create(), $handler));
        Assert::same($handler->calls(), 2);
    }

    /**
     * @param int<0, max> $maximumAttempts
     */
    private static function interceptor(int $maximumAttempts = 0): RetryInterceptor
    {
        return new RetryInterceptor(
            (new RetryOptions())
                ->withInitialInterval(1)
                ->withCongestionInitialInterval(1)
                ->withMaximumAttempts($maximumAttempts),
        );
    }

    /**
     * Fails the first `$times` calls with the given status code, then returns `'ok'`.
     */
    private static function failingHandler(int $times, int $code): RecordingHandler
    {
        return new RecordingHandler(
            static fn(CallContextInterface $ctx, int $call): string => $call <= $times
                ? throw new ServiceClientException((object) ['code' => $code, 'details' => 'error', 'metadata' => []])
                : 'ok',
        );
    }

    private static function catch(\Closure $fn): \Throwable
    {
        try {
            $fn();
        } catch (\Throwable $e) {
            return $e;
        }

        Assert::fail('Exception expected.');
    }
}
