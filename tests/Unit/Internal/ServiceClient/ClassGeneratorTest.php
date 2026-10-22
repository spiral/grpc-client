<?php

declare(strict_types=1);

namespace Spiral\Grpc\Client\Tests\Unit\Internal\ServiceClient;

use Spiral\Grpc\Client\Internal\ServiceClient\ClassGenerator;
use Spiral\Grpc\Client\Tests\Unit\Fixture\UnionReturnService;
use Spiral\Grpc\Client\Tests\Unit\Fixture\UntypedReturnService;
use Testo\Codecov\Covers;
use Testo\Expect;
use Testo\Test;

#[Test]
#[Covers(ClassGenerator::class)]
final class ClassGeneratorTest
{
    public function generateRejectsMethodWithoutReturnType(): never
    {
        Expect::exception(\RuntimeException::class)
            ->withMessage('Method Call must have a return type.');

        ClassGenerator::generate(UntypedReturnService::class);
    }

    public function generateRejectsMethodWithUnionReturnType(): never
    {
        Expect::exception(\RuntimeException::class)
            ->withMessage('Method Call must have a single named return type.');

        ClassGenerator::generate(UnionReturnService::class);
    }

    public function renderParameterTypesRejectsDnfType(): never
    {
        $type = (new \ReflectionFunction(static fn(): (\Countable&\Traversable)|null => null))->getReturnType();
        \assert($type instanceof \ReflectionType);

        Expect::exception(\Exception::class)
            ->withMessage('Unknown type.');

        ClassGenerator::renderParameterTypes($type, new \ReflectionClass(self::class));
    }
}
