<?php

declare(strict_types=1);

namespace Spiral\Grpc\Client\Tests\Unit\Interceptor;

use Spiral\Core\FactoryInterface;
use Spiral\Grpc\Client\Interceptor\Helper;
use Spiral\Grpc\Client\Interceptor\SetTimeoutInterceptor;
use Spiral\Interceptors\Context\CallContext;
use Spiral\Interceptors\Context\CallContextInterface;
use Spiral\Interceptors\Context\Target;
use Spiral\Interceptors\HandlerInterface;
use Testo\Assert;
use Testo\Codecov\Covers;
use Testo\Data\DataSet;
use Testo\Expect;
use Testo\Test;

#[Test]
#[Covers(SetTimeoutInterceptor::class)]
final class SetTimeoutInterceptorTest
{
    public function acceptsOneMillisecond(): void
    {
        $interceptor = new SetTimeoutInterceptor(1);

        $options = self::interceptOptions($interceptor);

        Assert::same($options['timeout'], 1000);
    }

    public function createConfigAcceptsOneMillisecond(): void
    {
        $factory = new class implements FactoryInterface {
            public function make(string $alias, array $parameters = []): mixed
            {
                return new $alias(...$parameters);
            }
        };

        // Autowire's `parameters` are private before spiral/core 3.15, so check the interceptor it builds
        $interceptor = SetTimeoutInterceptor::createConfig(1)->resolve($factory);

        Assert::instanceOf($interceptor, SetTimeoutInterceptor::class);
        Assert::same(self::interceptOptions($interceptor)['timeout'], 1000);
    }

    public function convertsMillisecondsToMicroseconds(): void
    {
        $interceptor = new SetTimeoutInterceptor(5_000);

        $options = self::interceptOptions($interceptor);

        Assert::same($options['timeout'], 5_000_000);
    }

    public function nullRemovesTimeout(): void
    {
        $interceptor = new SetTimeoutInterceptor();

        $options = self::interceptOptions($interceptor, ['timeout' => 1000, 'foo' => 'bar']);

        Assert::same($options, ['foo' => 'bar']);
    }

    #[DataSet([0])]
    #[DataSet([-1])]
    public function rejectsNonPositiveTimeout(int $timeout): never
    {
        Expect::exception(\InvalidArgumentException::class);

        new SetTimeoutInterceptor($timeout);
    }

    #[DataSet([0])]
    #[DataSet([-1])]
    public function createConfigRejectsNonPositiveTimeout(int $timeout): never
    {
        Expect::exception(\InvalidArgumentException::class);

        SetTimeoutInterceptor::createConfig($timeout);
    }

    /**
     * @param array<non-empty-string, scalar> $options
     * @return array<non-empty-string, scalar>
     */
    private static function interceptOptions(SetTimeoutInterceptor $interceptor, array $options = []): array
    {
        $context = new CallContext(Target::fromPathString('/service/method'), [null, '/service/method', null, [], [], $options]);
        $handler = new class implements HandlerInterface {
            public ?CallContextInterface $context = null;

            public function handle(CallContextInterface $context): mixed
            {
                $this->context = $context;
                return null;
            }
        };

        $interceptor->intercept($context, $handler);

        \assert($handler->context !== null);
        return Helper::getOptions($handler->context);
    }
}
