<?php

declare(strict_types=1);

namespace Spiral\Grpc\Client\Tests\Unit\Fixture;

use Google\Protobuf\GPBEmpty;

interface MissingMessageService
{
    public function Call(mixed $ctx): GPBEmpty;
}
