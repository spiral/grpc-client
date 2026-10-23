<?php

declare(strict_types=1);

namespace Spiral\Grpc\Client\Tests\Unit\Fixture;

use Google\Protobuf\GPBEmpty;
use Spiral\Grpc\Client\Config\ServiceConfig;
use Spiral\Grpc\Client\Interceptor\Helper;
use Spiral\Grpc\Client\Internal\Connection\ConnectionInterface;
use Spiral\Grpc\Client\Internal\ServiceClient\ServiceConfigMap;
use Spiral\Interceptors\Context\CallContext;
use Spiral\Interceptors\Context\CallContextInterface;
use Spiral\Interceptors\Context\Target;

/**
 * Builds a call context with the argument layout of {@see \Spiral\Grpc\Client\Internal\ServiceClient\ServiceClientTrait::_handle()}.
 */
final class CallContextFactory
{
    /**
     * @param list<ConnectionInterface> $connections The first one becomes the current connection.
     * @param ServiceConfigMap|null $serviceConfigs Left out of the context when null.
     */
    public static function create(
        array $connections = [],
        array $options = [],
        ?ServiceConfigMap $serviceConfigs = null,
    ): CallContextInterface {
        $connections === [] and $connections = [new FakeConnection()];

        $context = new CallContext(
            Target::fromClosure(static fn(): null => null),
            arguments: [
                $connections[0],
                '/test.Service/Method',
                new GPBEmpty(),
                [GPBEmpty::class, 'decode'],
                [],
                $options,
            ],
        );
        $context = Helper::withConnections($context, $connections);

        return $serviceConfigs === null ? $context : Helper::withServiceConfigs($context, $serviceConfigs);
    }

    /**
     * @param array{ConnectionInterface, ServiceConfig} ...$pairs
     */
    public static function serviceConfigs(array ...$pairs): ServiceConfigMap
    {
        return new ServiceConfigMap($pairs);
    }
}
