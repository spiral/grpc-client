<?php

declare(strict_types=1);

namespace Spiral\Grpc\Client\Tests\Acceptance;

use Spiral\RoadRunner\GRPC\ContextInterface;

final class GrpcContext implements ContextInterface
{
    public function __construct(
        private array $values = [],
    ) {}

    public function withValue(string $key, mixed $value): self
    {
        $clone = clone $this;
        $clone->values[$key] = $value;
        return $clone;
    }

    public function getValue(string $key): mixed
    {
        return $this->values[$key] ?? null;
    }

    public function getValues(): array
    {
        return $this->values;
    }
}
