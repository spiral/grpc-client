<?php

declare(strict_types=1);

namespace Spiral\Grpc\Client\Tests\Unit\Internal\ServiceClient;

use Spiral\Grpc\Client\Internal\ServiceClient\ClassGenerator;
use Spiral\Grpc\Client\Tests\Unit\Fixture\NamingServiceInterface;
use Spiral\Grpc\Client\Tests\Unit\Fixture\SignaturesInterface;
use Spiral\Grpc\Client\Tests\Unit\Fixture\TestServiceInterface;
use Spiral\Grpc\Client\Tests\Unit\Fixture\UnionReturnService;
use Spiral\Grpc\Client\Tests\Unit\Fixture\UntypedReturnService;
use Testo\Assert;
use Testo\Codecov\Covers;
use Testo\Data\DataProvider;
use Testo\Expect;
use Testo\Skip;
use Testo\Test;

#[Test]
#[Covers(ClassGenerator::class)]
final class ClassGeneratorTest
{
    public static function provideSignatures(): iterable
    {
        yield 'by reference' => ['reference', 'public function &reference(mixed &$items): array {'];
        yield 'union' => ['union', 'public function union(mixed $value): string|int {'];
        yield 'intersection' => ['intersection', 'public function intersection(): \Countable&\Traversable {'];
        yield 'nullable builtin' => ['nullable', 'public function nullable(): ?int {'];
        yield 'nullable class' => ['nullableClass', 'public function nullableClass(): ?\DateTimeInterface {'];
        yield 'self' => ['self', 'public function self(): \\' . SignaturesInterface::class . ' {'];
        yield 'mixed' => ['mixed', 'public function mixed(): mixed {'];
        yield 'variadic' => ['variadic', 'public function variadic(mixed ...$values): void {'];
    }

    public static function provideDefaults(): iterable
    {
        yield 'own constant' => ['own', 'self::LIMIT'];
        yield 'global constant' => ['global', '\PHP_INT_MAX'];
        yield 'scalar' => ['scalar', "'text'"];
        yield 'array' => ['array', "array (\n  0 => 1,\n  1 => 2,\n)"];
        yield 'new expression' => ['object', 'new \stdClass()'];
    }

    #[DataProvider('provideSignatures')]
    public function methodSignatureIsRendered(string $method, string $expected): void
    {
        $code = ClassGenerator::renderMethod(new \ReflectionMethod(SignaturesInterface::class, $method), 'BODY');

        Assert::same($code, "$expected\nBODY\n}");
    }

    public function methodWithoutReturnTypeIsRenderedWithoutOne(): void
    {
        $code = ClassGenerator::renderMethod(new \ReflectionMethod(UntypedReturnService::class, 'Call'));

        Assert::same($code, "public function Call(mixed \$ctx, mixed \$in) {\n\n}");
    }

    #[Skip('Bug: the `static` return type is treated as a class name and rendered as `\static`, which does not parse.')]
    public function staticReturnTypeIsRendered(): void
    {
        $code = ClassGenerator::renderMethod(new \ReflectionMethod(SignaturesInterface::class, 'create'));

        Assert::same($code, "public static function create(): static {\n\n}");
    }

    #[DataProvider('provideDefaults')]
    public function defaultValueIsRendered(string $parameter, string $expected): void
    {
        $param = new \ReflectionParameter([SignaturesInterface::class, 'defaults'], $parameter);

        Assert::same(ClassGenerator::renderDefaultValue($param), $expected);
        Assert::same(ClassGenerator::renderParameter($param), "mixed \$$parameter = $expected");
    }

    public function generatedClassImplementsTheInterface(): void
    {
        [$class, $code] = ClassGenerator::generate(TestServiceInterface::class);

        Assert::same(\substr($class, 0, \strrpos($class, '\\')), 'Spiral\Grpc\Client\Tests\Unit\Fixture');
        Assert::string($code)->contains('function Ping(mixed $ctx, mixed $in): \Google\Protobuf\GPBEmpty {');
        Assert::string($code)->contains("return \$this->_handle(__FUNCTION__, \$ctx, \$in, 'Google\Protobuf\GPBEmpty');");
    }

    public function generatedClassNameDoesNotClashWithExistingClass(): void
    {
        [$first, $code] = ClassGenerator::generate(NamingServiceInterface::class);
        eval($code);

        [$second] = ClassGenerator::generate(NamingServiceInterface::class);

        Assert::same($first, 'Spiral\Grpc\Client\Tests\Unit\Fixture\NamingServiceClient');
        Assert::same($second, 'Spiral\Grpc\Client\Tests\Unit\Fixture\NamingServiceClient1');
        Assert::true(\is_subclass_of($first, NamingServiceInterface::class));
    }

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
