<?php

declare(strict_types=1);

namespace Spiral\Grpc\Client\Tests\Unit\Fixture;

/**
 * Declares the signature shapes {@see \Spiral\Grpc\Client\Internal\ServiceClient\ClassGenerator} has to render.
 */
interface SignaturesInterface
{
    public const LIMIT = 10;

    public static function create(): static;

    public function &reference(array &$items): array;

    public function union(int|string $value): int|string;

    public function intersection(): \Countable&\Traversable;

    public function nullable(): ?int;

    public function nullableClass(): ?\DateTimeInterface;

    public function self(): self;

    public function mixed(): mixed;

    public function variadic(string ...$values): void;

    public function defaults(
        int $own = self::LIMIT,
        int $global = \PHP_INT_MAX,
        string $scalar = 'text',
        ?array $array = [1, 2],
        ?object $object = new \stdClass(),
    ): void;
}
