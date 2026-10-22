<?php

declare(strict_types=1);

namespace Spiral\Grpc\Client\Tests\Unit\Fixture;

interface UntypedReturnService
{
    public function Call(mixed $ctx, mixed $in);
}
