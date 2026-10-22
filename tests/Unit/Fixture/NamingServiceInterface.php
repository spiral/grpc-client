<?php

declare(strict_types=1);

namespace Spiral\Grpc\Client\Tests\Unit\Fixture;

use Google\Protobuf\GPBEmpty;
use Spiral\RoadRunner\GRPC\ContextInterface;
use Spiral\RoadRunner\GRPC\ServiceInterface;

/**
 * Reserved for the class naming test: no other test may generate a client for it.
 */
interface NamingServiceInterface extends ServiceInterface
{
    public const NAME = 'test.Naming';

    public function Ping(ContextInterface $ctx, GPBEmpty $in): GPBEmpty;
}
