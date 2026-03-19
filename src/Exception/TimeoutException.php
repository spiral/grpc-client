<?php

declare(strict_types=1);

namespace Spiral\Grpc\Client\Exception;

final class TimeoutException extends \RuntimeException implements GrpcClientException {}
