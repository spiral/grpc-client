<?php

declare(strict_types=1);

namespace Spiral\Grpc\Client\Interceptor;

use Spiral\Core\Container\Autowire;
use Spiral\Grpc\Client\Exception\ServiceClientException;
use Spiral\Grpc\Client\Exception\TimeoutException;
use Spiral\Grpc\Client\Interceptor\RetryInterceptor\RetryOptions;
use Spiral\Grpc\Client\Internal\BackoffThrottler;
use Spiral\Grpc\Client\Internal\StatusCode;
use Spiral\Interceptors\Context\CallContextInterface;
use Spiral\Interceptors\HandlerInterface;
use Spiral\Interceptors\InterceptorInterface;

final class RetryInterceptor implements InterceptorInterface
{
    public const RETRYABLE_ERRORS = [
        StatusCode::ResourceExhausted,
        StatusCode::Unavailable,
        StatusCode::Aborted,
        StatusCode::DeadlineExceeded,
    ];

    private const DEFAULT_INITIAL_INTERVAL_MS = 50;

    private const DEFAULT_CONGESTION_INITIAL_INTERVAL_MS = 1000;

    public function __construct(
        private readonly RetryOptions $defaultRetryOptions,
    ) {}

    /**
     * @param int<0, max>|null $initialInterval Initial interval in milliseconds.
     *        Default to 50ms.
     * @param int<0, max>|null $congestionInitialInterval Initial interval on congestion related failures
     *        (i.e. {@see StatusCode::ResourceExhausted}) in milliseconds.
     *        Default to 1000ms.
     * @param float $backoffCoefficient Coefficient used to calculate the next retry backoff interval.
     *        Default is 2.0.
     * @param int<0, max>|null $maximumInterval Maximum backoff interval between retries in milliseconds.
     *        Default is 100x of {@see $initialInterval}.
     * @param int<0, max> $maximumAttempts Maximum number of attempts.
     * @param float|null $maximumJitterCoefficient Maximum jitter to apply.
     */
    public static function createAutowireConfig(
        ?int $initialInterval = RetryOptions::DEFAULT_INITIAL_INTERVAL,
        ?int $congestionInitialInterval = RetryOptions::DEFAULT_CONGESTION_INITIAL_INTERVAL,
        float $backoffCoefficient = RetryOptions::DEFAULT_BACKOFF_COEFFICIENT,
        ?int $maximumInterval = RetryOptions::DEFAULT_MAXIMUM_INTERVAL,
        int $maximumAttempts = RetryOptions::DEFAULT_MAXIMUM_ATTEMPTS,
        ?float $maximumJitterCoefficient = null,
    ): Autowire {
        return (new RetryOptions())
            ->withInitialInterval($initialInterval)
            ->withCongestionInitialInterval($congestionInitialInterval)
            ->withBackoffCoefficient($backoffCoefficient)
            ->withMaximumInterval($maximumInterval)
            ->withMaximumAttempts($maximumAttempts)
            ->withMaximumJitterCoefficient($maximumJitterCoefficient)
            ->toAutowire();
    }

    public function intercept(CallContextInterface $context, HandlerInterface $handler): mixed
    {
        $attempt = 0;
        $initialIntervalMs = $congestionInitialIntervalMs = $throttler = null;
        $retryOption = $context->getAttribute(RetryOptions::class, $this->defaultRetryOptions);
        \assert($retryOption instanceof RetryOptions);

        do_try:
        ++$attempt;
        try {
            return $handler->handle($context);
        } catch (ServiceClientException $e) {
            $errorCode = StatusCode::tryFrom($e->getCode()) ?? throw $e;
            if (!\in_array($errorCode, self::RETRYABLE_ERRORS, true)) {
                $errorCode === StatusCode::DeadlineExceeded and throw new TimeoutException(
                    $e->getMessage(),
                    StatusCode::DeadlineExceeded->value,
                    $e,
                );

                // non retryable
                throw $e;
            }

            if ($retryOption->maximumAttempts !== 0 && $attempt >= $retryOption->maximumAttempts) {
                // Reached maximum attempts
                throw $e;
            }

            // Init interval values in milliseconds
            $initialIntervalMs ??= $retryOption->initialInterval ?? self::DEFAULT_INITIAL_INTERVAL_MS;
            $congestionInitialIntervalMs ??= $retryOption->congestionInitialInterval
                ?? self::DEFAULT_CONGESTION_INITIAL_INTERVAL_MS;

            $throttler ??= new BackoffThrottler(
                maxInterval: $retryOption->maximumInterval ?? $initialIntervalMs * 200,
                maxJitterCoefficient: $retryOption->maximumJitterCoefficient,
                backoffCoefficient: $retryOption->backoffCoefficient,
            );

            // Initial interval always depends on the *most recent* failure.
            $baseInterval = $e->getCode() === StatusCode::ResourceExhausted->value
                ? $congestionInitialIntervalMs
                : $initialIntervalMs;

            $wait = $throttler->calculateSleepTime(
                failureCount: $attempt,
                initialInterval: $baseInterval,
            );

            // wait till the next call
            $this->usleep($wait);
        }
        goto do_try;
    }

    /**
     * @param int<0, max> $param Delay in microseconds
     */
    private function usleep(int $param): void
    {
        if (\Fiber::getCurrent() === null) {
            \usleep($param);
            return;
        }

        $deadline = \microtime(true) + (float) ($param / 1_000_000);

        while (\microtime(true) < $deadline) {
            \Fiber::suspend();
        }
    }
}
