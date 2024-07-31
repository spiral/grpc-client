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

    /**
     * @return object {@see Message} or custom message object
     */
    public static function getMessage(CallContextInterface $context): object
    {
        return $context->getArguments()[4];
    }

    /**
     * @param object $argument Should be {@see Message} at the end
     */
    public static function withMessage(
        CallContextInterface $context,
        object $argument,
    ): CallContextInterface {
        $args = $context->getArguments();
        $args[2] = $argument;
        return $context->withArguments($args);
    }

    /**
     * @return array<non-empty-string, list<scalar>>
     */
    public static function getMetadata(CallContextInterface $context): array
    {
        return $context->getArguments()[2];
    }

    /**
     * @param array<non-empty-string, list<scalar>> $metadata
     */
    public static function withMetadata(
        CallContextInterface $context,
        array $metadata,
    ): CallContextInterface {
        $args = $context->getArguments();
        $args[4] = $metadata;
        return $context->withArguments($args);
    }

    /**
     * @return class-string
     */
    public static function getReturnType(CallContextInterface $context): string
    {
        return $context->getArguments()[3][0];
    }

    /**
     * @param class-string $returnType
     */
    public static function withReturnType(
        CallContextInterface $context,
        string $returnType,
    ): CallContextInterface {
        $args = $context->getArguments();
        $args[3][0] = $returnType;
        return $context->withArguments($args);
    }

    /**
     * @return array<non-empty-string, scalar>
     */
    public static function getOptions(CallContextInterface $context): array
    {
        return $context->getArguments()[5];
    }

    public static function withOptions(
        CallContextInterface $context,
        array $options,
    ): CallContextInterface {
        $args = $context->getArguments();
        $args[5] = $options;
        return $context->withArguments($args);
    }
}
