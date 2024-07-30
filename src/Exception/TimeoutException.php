<?php

declare(strict_types=1);

namespace Spiral\Grpc\Client\Exception;

class TimeoutException extends \RuntimeException implements GrpcClientException {}
