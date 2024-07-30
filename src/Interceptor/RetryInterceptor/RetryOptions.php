<?php

declare(strict_types=1);

namespace Spiral\Grpc\Client\Interceptor\RetryInterceptor;

use Spiral\Core\Container\Autowire;
use Spiral\Grpc\Client\Interceptor\RetryInterceptor;
use Spiral\Grpc\Client\Internal\StatusCode;

/**
 * @psalm-immutable
 * @psalm-suppress DocblockTypeContradiction
 */
final class RetryOptions
{
    public const DEFAULT_INITIAL_INTERVAL = null;
    public const DEFAULT_CONGESTION_INITIAL_INTERVAL = null;
    public const DEFAULT_BACKOFF_COEFFICIENT = 2.0;
    public const DEFAULT_MAXIMUM_INTERVAL = null;
    public const DEFAULT_MAXIMUM_ATTEMPTS = 0;

    /**
     * Backoff interval for the first retry in milliseconds.
     * If {@see self::$backoffCoefficient} is 1.0, then it will be used for all retries.
     *
     * @var int<0, max>|null
     */
    public ?int $initialInterval = self::DEFAULT_INITIAL_INTERVAL;

    /**
     * Interval of the first retry, on congestion related failures (i.e. {@see StatusCode::ResourceExhausted}).
     *
     * If the coefficient is 1.0 then it is used for all retries.
     * Default to 1000ms.
     */
    public ?int $congestionInitialInterval = self::DEFAULT_CONGESTION_INITIAL_INTERVAL;

    /**
     * Coefficient used to calculate the next retry backoff interval.
     * The next retry interval is the previous interval multiplied by this coefficient.
     *
     * @note Must be greater than 1.0
     */
    public float $backoffCoefficient = self::DEFAULT_BACKOFF_COEFFICIENT;

    /**
     * Maximum backoff interval between retries.
     * Exponential backoff leads to interval increase.
     * This value is the cap of the interval.
     *
     * Default is 100x of {@see $initialInterval}.
     * @var int<0, max>|null
     */
    public ?int $maximumInterval = self::DEFAULT_MAXIMUM_INTERVAL;

    /**
     * Maximum number of attempts.
     * When exceeded, the retries stop even if not expired yet.
     * If not set or set to 0, it means unlimited.
     *
     * @var int<0, max>
     */
    public int $maximumAttempts = self::DEFAULT_MAXIMUM_ATTEMPTS;

    /**
     * Maximum amount of jitter to apply.
     * Must be lower than 1.
     *
     * 0.1 means that actual retry time can be +/- 10% of the calculated time.
     */
    public float $maximumJitterCoefficient = 0.1;

    public function toAutowire(): Autowire
    {
        return new Autowire(RetryInterceptor::class, [$this]);
    }

    /**
     * Backoff interval for the first retry in milliseconds.
     * If {@see self::$backoffCoefficient} is 1.0, then it will be used for all retries.
     *
     * @param int<0, max>|null $interval
     */
    public function withInitialInterval(?int $interval): self
    {
        $interval === null || $interval >= 0 or throw new \InvalidArgumentException('Invalid initial interval value.');

        $self = clone $this;
        $self->initialInterval = $interval;
        return $self;
    }

    /**
     * Interval of the first retry, on congestion related failures (i.e. RESOURCE_EXHAUSTED errors).
     * If coefficient is 1.0 then it is used for all retries. Defaults to 1000ms.
     *
     * @param int<0, max>|null $interval Interval to wait on first retry, on congestion failures.
     *        Defaults to 1000ms, which is used if set to {@see null}.
     *
     */
    public function withCongestionInitialInterval(?int $interval): self
    {
        $interval === null || $interval >= 0 or throw new \InvalidArgumentException(
            'Invalid congestion initial interval value.',
        );

        $self = clone $this;
        $self->congestionInitialInterval = $interval;
        return $self;
    }

    /**
     * Coefficient used to calculate the next retry backoff interval.
     * The next retry interval is the previous interval multiplied by this coefficient.
     *
     * @note Must be greater than 1.0
     */
    public function withBackoffCoefficient(float $coefficient): self
    {
        \assert($coefficient >= 1.0);

        $self = clone $this;
        $self->backoffCoefficient = $coefficient;
        return $self;
    }

    /**
     * Maximum backoff interval between retries.
     * Exponential backoff leads to interval increase.
     * This value is the cap of the interval.
     *
     * @param int<0, max>|null $interval
     */
    public function withMaximumInterval($interval): self
    {
        \assert($interval === null || $interval >= 0);

        $self = clone $this;
        $self->maximumInterval = $interval;
        return $self;
    }

    /**
     * Maximum number of attempts.
     * When exceeded, the retries stop even if not expired yet.
     *
     * @param int<0, max> $attempts
     */
    public function withMaximumAttempts(int $attempts): self
    {
        \assert($attempts >= 0);

        $self = clone $this;
        $self->maximumAttempts = $attempts;

        return $self;
    }

    /**
     * Maximum amount of jitter to apply.
     *
     * 0.2 means that actual retry time can be +/- 20% of the calculated time.
     * Set to 0 to disable jitter. Must be lower than 1.
     *
     * @param null|float $coefficient Maximum amount of jitter.
     *        Default will be used if set to {@see null}.
     */
    public function withMaximumJitterCoefficient(?float $coefficient): self
    {
        $coefficient === null || ($coefficient >= 0.0 && $coefficient < 1.0) or throw new \InvalidArgumentException(
            'Maximum jitter coefficient must be in range [0, 1).',
        );

        $self = clone $this;
        $self->maximumJitterCoefficient = $coefficient ?? 0.1;
        return $self;
    }
}
