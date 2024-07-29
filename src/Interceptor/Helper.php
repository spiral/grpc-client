<?php

declare(strict_types=1);

namespace Spiral\Grpc\Client\Interceptor;

use Spiral\Grpc\Client\Internal\Connection\Connection;
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
}
