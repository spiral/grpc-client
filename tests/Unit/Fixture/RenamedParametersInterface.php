<?php

declare(strict_types=1);

namespace Spiral\Grpc\Client\Tests\Unit\Fixture;

use Google\Protobuf\GPBEmpty;
use Spiral\RoadRunner\GRPC\ContextInterface;
use Spiral\RoadRunner\GRPC\ServiceInterface;

/**
 * A hand-written service interface that does not follow the generated `$ctx` / `$in` naming.
 */
interface RenamedParametersInterface extends ServiceInterface
{
    public const NAME = 'test.Renamed';

    public function Ping(ContextInterface $context, GPBEmpty $request): GPBEmpty;
}
