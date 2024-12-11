<?php

declare(strict_types=1);

namespace Spiral\Grpc\Client\Exception;

use Google\Protobuf\Any;
use Google\Protobuf\Internal\RepeatedField;
use Google\Rpc\Status;

class ServiceClientException extends \RuntimeException implements GrpcClientException
{
    private Status $status;

    /**
     * @throws \Exception
     */
    public function __construct(\stdClass $status, ?\Throwable $previous = null)
    {
        $this->status = new Status();

        if (isset($status->metadata['grpc-status-details-bin'][0])) {
            $this->status->mergeFromString($status->metadata['grpc-status-details-bin'][0]);
        }

        parent::__construct($status->details . " (code: $status->code)", $status->code, $previous);
    }

    public function getStatus(): Status
    {
        return $this->status;
    }

    public function getDetails(): RepeatedField
    {
        return $this->status->getDetails();
    }

    /**
     * @link https://dev.to/khepin/grpc-advanced-error-handling-from-go-to-php-1omc
     *
     * @throws \Exception
     */
    public function getFailure(string $class): ?object
    {
        $details = $this->getDetails();
        if ($details->count() === 0) {
            return null;
        }

        /** @var Any $detail */
        foreach ($details as $detail) {
            if ($detail->is($class)) {
                return $detail->unpack();
            }
        }

        return null;
    }
}
