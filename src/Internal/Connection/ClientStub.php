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

        $status->code === StatusCode::Ok->value or throw new ServiceClientException($status);

        return $result;
    }
}