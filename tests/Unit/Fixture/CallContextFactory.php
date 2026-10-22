<?php

declare(strict_types=1);

namespace Spiral\Grpc\Client\Tests\Unit\Fixture;

use Google\Protobuf\GPBEmpty;
use Spiral\Grpc\Client\Interceptor\Helper;
use Spiral\Grpc\Client\Internal\Connection\ConnectionInterface;
use Spiral\Interceptors\Context\CallContext;
use Spiral\Interceptors\Context\Target;

/**
 * Builds a call context with the argument layout of {@see \Spiral\Grpc\Client\Internal\ServiceClient\ServiceClientTrait::_handle()}.
 */
final class CallContextFactory
{
    /**
     * @param list<ConnectionInterface> $connections The first one becomes the current connection.
     */
    public static function create(array $connections = [], array $options = []): CallContext
    {
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

        return Helper::withConnections($context, $connections);
    }
}
