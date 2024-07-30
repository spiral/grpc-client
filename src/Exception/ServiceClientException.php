<?php

declare(strict_types=1);

namespace Spiral\Grpc\Client\Exception;

use Google\Protobuf\Any;
use Google\Protobuf\Internal\RepeatedField;
use Google\Rpc\Status;

class ServiceClientException extends \RuntimeException implements GrpcClientException
{
    /**
     * @var Status
     */
    private Status $status;

    /**
     * @param \stdClass $status
     * @param \Throwable|null $previous
     * @throws \Exception
     */
    public function __construct(\stdClass $status, \Throwable $previous = null)
    {
        $this->status = new Status();

        if (isset($status->metadata['grpc-status-details-bin'][0])) {
            $this->status->mergeFromString($status->metadata['grpc-status-details-bin'][0]);
        }

        parent::__construct($status->details . " (code: $status->code)", $status->code, $previous);
    }

    /**
     * @return Status
     */
    public function getStatus(): Status
    {
        return $this->status;
    }

    /**
     * @return RepeatedField
     */
    public function getDetails(): RepeatedField
    {
        return $this->status->getDetails();
    }

    /**
     * @link https://dev.to/khepin/grpc-advanced-error-handling-from-go-to-php-1omc
     *
     * @param string $class
     * @return object|null
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
