<?php

declare(strict_types=1);

use Google\Protobuf\GPBEmpty;
use Spiral\RoadRunner\GRPC\ContextInterface;
use Spiral\RoadRunner\GRPC\ServiceInterface;

/**
 * A service generated from a `.proto` without a package lands in the global namespace.
 * Not autoloaded: PSR-4 maps no class without a namespace.
 */
interface GlobalServiceInterface extends ServiceInterface
{
    public const NAME = 'GlobalService';

    public function Ping(ContextInterface $ctx, GPBEmpty $in): GPBEmpty;
}
