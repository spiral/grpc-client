<?php

declare(strict_types=1);

namespace Spiral\Grpc\Client\Tests\Unit\Fixture;

use Google\Protobuf\GPBEmpty;
use Spiral\Interceptors\Context\CallContextInterface;
use Spiral\Interceptors\HandlerInterface;
use Spiral\Interceptors\InterceptorInterface;

/**
 * Answers every call itself, so a service client can be exercised without a server.
 */
final class ShortCircuitInterceptor implements InterceptorInterface
{
    /** @var list<CallContextInterface> */
    public array $contexts = [];

    public function intercept(CallContextInterface $context, HandlerInterface $handler): mixed
    {
        $this->contexts[] = $context;
        return new GPBEmpty();
    }
}
