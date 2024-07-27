<?php

declare(strict_types=1);

namespace Spiral\Grpc\Client\Internal\Connection;

use Google\Protobuf\Internal\Message;
use Grpc\BaseStub;
use Spiral\Grpc\Client\Exception\ServiceClientException;

/**
 * @internal
 */
class ClientStub extends BaseStub
{
    public function invoke(
        string $method,
        Message $in,
        callable $deserializer,
        array $metadata,
        array $options,
    ) {
        [$result, $status] = $this->_simpleRequest(
            $method,
            $in,
            $deserializer,
            $metadata,
            $options,
        )->wait();

        if ($status->code !== 0) {
            throw new ServiceClientException($status);
        }

        return $result;
    }
}
