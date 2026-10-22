<?php

declare(strict_types=1);

namespace Spiral\Grpc\Client\Exception;

use Google\Protobuf\Any;
use Google\Protobuf\Internal\RepeatedField;
use Google\Rpc\Status;

final class ServiceClientException extends \RuntimeException implements GrpcClientException
{
    private Status $status;

    /**
     * @param \stdClass $status Call status from ext-grpc with `code`, `details` and `metadata` properties.
     *
     * @throws \Exception
     */
    public function __construct(\stdClass $status, ?\Throwable $previous = null)
    {
        $this->status = new Status();

        /** @var array<string, list<string>> $metadata */
        $metadata = $status->metadata ?? [];
        if (isset($metadata['grpc-status-details-bin'][0])) {
            $this->status->mergeFromString($metadata['grpc-status-details-bin'][0]);
        }

        /** @var int $code */
        $code = $status->code;
        /** @var string $details */
        $details = $status->details;
        parent::__construct($details . " (code: $code)", $code, $previous);
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
