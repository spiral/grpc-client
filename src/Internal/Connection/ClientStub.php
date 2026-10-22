<?php

declare(strict_types=1);

namespace Spiral\Grpc\Client\Internal\Connection;

use Google\Protobuf\Internal\Message;
use Grpc\BaseStub;
use Spiral\Grpc\Client\Exception\ServiceClientException;
use Spiral\Grpc\Client\Internal\StatusCode;

/**
 * @internal
 */
final class ClientStub extends BaseStub
{
    /**
     * @param non-empty-string $method
     * @param array{class-string<Message>, non-empty-string} $deserializer The response class and a method
     *        name: grpc/grpc instantiates the class and calls `mergeFromString()`, ignoring the name.
     */
    public function invoke(
        string $method,
        Message $in,
        array $deserializer,
        array $metadata,
        array $options,
    ): Message {
        [$result, $status] = $this->_simpleRequest(
            $method,
            $in,
            $deserializer,
            $metadata,
            $options,
        )->wait();

        $status->code === StatusCode::Ok->value or throw new ServiceClientException($status);

        \assert($result instanceof Message);
        return $result;
    }
}
