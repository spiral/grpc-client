<?php

declare(strict_types=1);

namespace Spiral\Grpc\Client\Tests\Unit\Exception;

use Google\Protobuf\Any;
use Google\Rpc\ErrorInfo;
use Google\Rpc\RetryInfo;
use Google\Rpc\Status;
use Spiral\Grpc\Client\Exception\ServiceClientException;
use Spiral\Grpc\Client\Internal\StatusCode;
use Testo\Assert;
use Testo\Codecov\Covers;
use Testo\Test;

#[Test]
#[Covers(ServiceClientException::class)]
final class ServiceClientExceptionTest
{
    public function messageAndCodeComeFromStatus(): void
    {
        $previous = new \RuntimeException();

        $e = new ServiceClientException(self::status(StatusCode::NotFound->value, 'no such thing'), $previous);

        Assert::same($e->getMessage(), 'no such thing (code: 5)');
        Assert::same($e->getCode(), StatusCode::NotFound->value);
        Assert::same($e->getPrevious(), $previous);
    }

    public function statusIsEmptyWithoutDetailsMetadata(): void
    {
        $e = new ServiceClientException(self::status(StatusCode::Internal->value));

        Assert::same($e->getDetails()->count(), 0);
        Assert::same($e->getFailure(ErrorInfo::class), null);
    }

    public function statusDetailsAreDecodedFromMetadata(): void
    {
        $info = new ErrorInfo(['reason' => 'QUOTA', 'domain' => 'example.com']);

        $e = new ServiceClientException(self::status(StatusCode::ResourceExhausted->value, details: [$info]));

        Assert::same($e->getStatus()->getCode(), StatusCode::ResourceExhausted->value);
        Assert::same($e->getDetails()->count(), 1);
        $failure = $e->getFailure(ErrorInfo::class);
        Assert::instanceOf($failure, ErrorInfo::class);
        Assert::same($failure->getReason(), 'QUOTA');
        Assert::same($failure->getDomain(), 'example.com');
    }

    public function failureOfAnotherTypeIsNotFound(): void
    {
        $e = new ServiceClientException(
            self::status(StatusCode::ResourceExhausted->value, details: [new ErrorInfo(['reason' => 'QUOTA'])]),
        );

        Assert::same($e->getFailure(RetryInfo::class), null);
    }

    /**
     * Mimics the status object returned by `\Grpc\UnaryCall::wait()`.
     *
     * @param list<\Google\Protobuf\Internal\Message> $details
     */
    private static function status(int $code, string $message = 'error', array $details = []): \stdClass
    {
        $metadata = [];
        if ($details !== []) {
            $status = new Status(['code' => $code, 'message' => $message]);
            $status->setDetails(\array_map(static function (\Google\Protobuf\Internal\Message $detail): Any {
                $any = new Any();
                $any->pack($detail);
                return $any;
            }, $details));
            $metadata['grpc-status-details-bin'] = [$status->serializeToString()];
        }

        return (object) ['code' => $code, 'details' => $message, 'metadata' => $metadata];
    }
}
