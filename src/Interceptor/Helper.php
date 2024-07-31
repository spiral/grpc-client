<?php

declare(strict_types=1);

namespace Spiral\Grpc\Client\Interceptor;

use Spiral\Grpc\Client\Internal\Connection\ConnectionInterface as Connection;
use Spiral\Interceptors\Context\CallContextInterface;

final class Helper
{
    public const ATTR_CONNECTIONS = 'connections';

    /**
     * @return Connection[]
     */
    public static function getConnections(CallContextInterface $context): array
    {
        return $context->getAttribute(self::ATTR_CONNECTIONS, []);
    }

    /**
     * @param Connection[] $connections
     */
    public static function withConnections(CallContextInterface $context, array $connections): CallContextInterface
    {
        return $context->withAttribute(self::ATTR_CONNECTIONS, $connections);
    }

    public static function getCurrentConnection(CallContextInterface $context): Connection
    {
        return $context->getArguments()[0];
    }

    public static function withCurrentConnection(
        CallContextInterface $context,
        Connection $connection,
    ): CallContextInterface {
        $args = $context->getArguments();
        $args[0] = $connection;
        return $context->withArguments($args);
    }
}
