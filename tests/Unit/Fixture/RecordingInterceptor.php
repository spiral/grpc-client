<?php

declare(strict_types=1);

namespace Spiral\Grpc\Client\Tests\Unit\Fixture;

use Spiral\Interceptors\Context\CallContextInterface;
use Spiral\Interceptors\HandlerInterface;
use Spiral\Interceptors\InterceptorInterface;

/**
 * Appends its name to a shared log and passes the call on.
 */
final class RecordingInterceptor implements InterceptorInterface
{
    /** @var list<non-empty-string> */
    public static array $log = [];

    /**
     * @param non-empty-string $name
     */
    public function __construct(
        private readonly string $name = 'default',
    ) {}

    public function intercept(CallContextInterface $context, HandlerInterface $handler): mixed
    {
        self::$log[] = $this->name;
        return $handler->handle($context);
    }
}
