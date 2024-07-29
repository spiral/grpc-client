<?php

declare(strict_types=1);

namespace Spiral\Grpc\Client\Internal\Interceptor;

use Spiral\Interceptors\Context\CallContextInterface;
use Spiral\Interceptors\HandlerInterface;

/**
 * @internal
 */
final class GrpcServiceCallHandler implements HandlerInterface
{
    public function handle(CallContextInterface $context): mixed
    {
        $callable = $context->getTarget()->getCallable();
        \is_callable($callable) or throw new \RuntimeException('Callable not found in the call context.');

        /** @see \Spiral\Grpc\Client\Internal\ServiceClient\ServiceClientTrait::_invoke() */
        return $callable(...$context->getArguments());
    }
}
