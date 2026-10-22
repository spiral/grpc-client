<?php

declare(strict_types=1);

namespace Spiral\Grpc\Client\Interceptor;

use Spiral\Grpc\Client\Internal\Connection\ConnectionInterface as Connection;
use Spiral\Interceptors\Context\CallContextInterface;

final class Helper
{
    public const ATTR_CONNECTIONS = 'connections';

    /**
     * @return list<Connection>
     */
    public static function getConnections(CallContextInterface $context): array
    {
        /** @var list<Connection> $connections */
        $connections = $context->getAttribute(self::ATTR_CONNECTIONS, []);
        return $connections;
    }

    /**
     * @param list<Connection> $connections
     */
    public static function withConnections(CallContextInterface $context, array $connections): CallContextInterface
    {
        return $context->withAttribute(self::ATTR_CONNECTIONS, $connections);
    }

    public static function getCurrentConnection(CallContextInterface $context): Connection
    {
        /** @var Connection $connection */
        $connection = $context->getArguments()[0];
        return $connection;
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
        /** @var object $message */
        $message = $context->getArguments()[2];
        return $message;
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
        /** @var array<non-empty-string, list<scalar>> $metadata */
        $metadata = $context->getArguments()[4];
        return $metadata;
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
        /** @var array{class-string, non-empty-string} $deserializer */
        $deserializer = $context->getArguments()[3];
        return $deserializer[0];
    }

    /**
     * @param class-string $returnType
     */
    public static function withReturnType(
        CallContextInterface $context,
        string $returnType,
    ): CallContextInterface {
        $args = $context->getArguments();
        /** @var array{class-string, non-empty-string} $deserializer */
        $deserializer = $args[3];
        $deserializer[0] = $returnType;
        $args[3] = $deserializer;
        return $context->withArguments($args);
    }

    /**
     * @return array<non-empty-string, scalar>
     */
    public static function getOptions(CallContextInterface $context): array
    {
        /** @var array<non-empty-string, scalar> $options */
        $options = $context->getArguments()[5];
        return $options;
    }

    /**
     * @param array<non-empty-string, scalar> $options
     */
    public static function withOptions(
        CallContextInterface $context,
        array $options,
    ): CallContextInterface {
        $args = $context->getArguments();
        $args[5] = $options;
        return $context->withArguments($args);
    }
}
