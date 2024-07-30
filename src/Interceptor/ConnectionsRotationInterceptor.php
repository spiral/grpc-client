<?php

declare(strict_types=1);

namespace Spiral\Grpc\Client\Interceptor;

use Spiral\Grpc\Client\Exception\ServiceClientException;
use Spiral\Grpc\Client\Internal\Connection\ConnectionInterface as Connection;
use Spiral\Interceptors\Context\CallContextInterface;
use Spiral\Interceptors\HandlerInterface;
use Spiral\Interceptors\InterceptorInterface;

final class ConnectionsRotationInterceptor implements InterceptorInterface
{
    public function intercept(CallContextInterface $context, HandlerInterface $handler): mixed
    {
        $connections = Helper::getConnections($context);

        // If we don't have multiple connections, we don't need to do anything
        if (\count($connections) <= 1) {
            return $handler->handle($context);
        }

        // Connected must be first
        \usort($connections, static fn(Connection $a, Connection $b) => $b->isConnected() <=> $a->isConnected());

        // Just try the next connection if the current one failed
        trying:
        $connection = \array_shift($connections);
        try {
            return $handler->handle(Helper::withCurrentConnection($context, $connection));
        } catch (ServiceClientException $e) {
            \count($connections) > 0 or throw $e;

            Helper::withConnections($context, $connections);
            goto trying;
        }
    }
}
