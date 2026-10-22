<?php

declare(strict_types=1);

namespace Spiral\Grpc\Client\Tests\Unit\Fixture;

use Google\Protobuf\GPBEmpty;
use Spiral\RoadRunner\GRPC\ContextInterface;
use Spiral\RoadRunner\GRPC\ServiceInterface;

interface OtherServiceInterface extends ServiceInterface
{
    public const NAME = 'test.Other';

    public function Ping(ContextInterface $ctx, GPBEmpty $in): GPBEmpty;
}
