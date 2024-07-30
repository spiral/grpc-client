<?php

declare(strict_types=1);

namespace Spiral\Grpc\Client\Internal\ServiceClient;

final class ClassGenerator
{
    /**
     * @template T
     *
     * @param class-string<T> $interface
     *
     * @return array{class-string<T&ServiceClientInterface>, non-empty-string}
     */
    public static function generate(string $interface): array
    {
        $namespace = \substr($interface, 0, \strrpos($interface, '\\'));
        $reflection = new \ReflectionClass($interface);
        /** @var non-empty-string[] $methods */
        $methods = [];

        foreach ($reflection->getMethods() as $method) {
            $returnType = $method->getReturnType()?->getName() ?? throw new \RuntimeException(
                "Method {$method->getName()} must have a return type.",
            );
            /** @see ServiceClientTrait::_handle() */
            $methods[] = self::renderMethod(
                $method,
                <<<PHP
                return \$this->_handle(__FUNCTION__, \$ctx, \$in, '$returnType');
                PHP,
            );
        }

        $methodsStr = \implode("\n", $methods);
        $base = \str_replace('Interface', '', $reflection->getShortName()) . 'Client';
        $i = 0;
        do {
            $newClassName = $base . ($i++ === 0 ? '' : $i);
            $fullClassName = $namespace . '\\' . $newClassName;
        } while (\class_exists($fullClassName));

        /** @var class-string<T&ServiceClientInterface> $fullClassName */
        return [$fullClassName, <<<PHP
            namespace $namespace;
            final class GeneratedServiceClient implements
                \\$interface,
                \Spiral\Grpc\Client\Internal\ServiceClient\ServiceClientInterface
            {
                use \Spiral\Grpc\Client\Internal\ServiceClient\ServiceClientTrait;
                $methodsStr
            }
            PHP];
    }

    public static function renderMethod(\ReflectionMethod $m, string $body = ''): string
    {
        return \sprintf(
            "public%s function %s%s(%s)%s {\n%s\n}",
            $m->isStatic() ? ' static' : '',
            $m->returnsReference() ? '&' : '',
            $m->getName(),
            \implode(', ', \array_map([self::class, 'renderParameter'], $m->getParameters())),
            $m->hasReturnType()
                ? ': ' . self::renderParameterTypes($m->getReturnType(), $m->getDeclaringClass())
                : '',
            $body,
        );
    }

    public static function renderParameter(\ReflectionParameter $param): string
    {
        return \ltrim(
            \sprintf(
                '%s %s%s%s%s',
                $param->hasType() ? self::renderParameterTypes($param->getType(), $param->getDeclaringClass()) : '',
                $param->isPassedByReference() ? '&' : '',
                $param->isVariadic() ? '...' : '',
                '$' . $param->getName(),
                $param->isOptional() && !$param->isVariadic() ? ' = ' . self::renderDefaultValue($param) : '',
            ),
            ' ',
        );
    }

    public static function renderParameterTypes(\ReflectionType $types, \ReflectionClass $class): string
    {
        if ($types instanceof \ReflectionNamedType) {
            return ($types->allowsNull() && $types->getName() !== 'mixed' ? '?' : '') . ($types->isBuiltin()
                    ? $types->getName()
                    : self::normalizeClassType($types, $class));
        }

        [$separator, $types] = match (true) {
            $types instanceof \ReflectionUnionType => ['|', $types->getTypes()],
            $types instanceof \ReflectionIntersectionType => ['&', $types->getTypes()],
            default => throw new \Exception('Unknown type.'),
        };

        $result = [];
        foreach ($types as $type) {
            $result[] = $type->isBuiltin()
                ? $type->getName()
                : self::normalizeClassType($type, $class);
        }

        return \implode($separator, $result);
    }

    public static function renderDefaultValue(\ReflectionParameter $param): string
    {
        if ($param->isDefaultValueConstant()) {
            $result = $param->getDefaultValueConstantName();

            return \explode('::', $result)[0] === 'self'
                ? $result
                : '\\' . $result;
        }

        $cut = self::cutDefaultValue($param);

        return \str_starts_with($cut, 'new ')
            ? $cut
            : \var_export($param->getDefaultValue(), true);
    }

    public static function normalizeClassType(\ReflectionNamedType $type, \ReflectionClass $class): string
    {
        return '\\' . ($type->getName() === 'self' ? $class->getName() : $type->getName());
    }

    private static function cutDefaultValue(\ReflectionParameter $param): string
    {
        $string = (string) $param;

        return \trim(\substr($string, \strpos($string, '=') + 1, -1));
    }
}
