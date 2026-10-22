<?php

declare(strict_types=1);

namespace Spiral\Grpc\Client\Tests\Unit\Fixture;

use Google\Protobuf\GPBEmpty;
use Google\Protobuf\StringValue;

interface UnionReturnService
{
    public function Call(mixed $ctx, mixed $in): StringValue|GPBEmpty;
}
