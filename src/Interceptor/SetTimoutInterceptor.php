<?php

declare(strict_types=1);

namespace Spiral\Grpc\Client\Interceptor;

use Spiral\Core\Container\Autowire;
use Spiral\Interceptors\Context\CallContextInterface;
use Spiral\Interceptors\HandlerInterface;
use Spiral\Interceptors\InterceptorInterface;

/**
 * Apply retry logic to the gRPC call.
 *
 * Use {@see RetryInterceptor::createAutowireConfig()} to create a config DTO in a configuration file.
 */
final class SetTimoutInterceptor implements InterceptorInterface
{
    /**
     * @param int<1, max>|null $timeout Timeout in milliseconds.
     */
    public function __construct(
        private readonly ?int $timeout = null,
    ) {
        $timeout === null || $timeout > 1 or throw new \InvalidArgumentException(
            'Timeout must be greater than 0 or `null`.',
        );
    }

    /**
     * @param int<1, max>|null $timeout Timeout in milliseconds.
     */
    public static function createAutowireConfig(?int $timeout = null): Autowire
    {
        $timeout === null || $timeout > 1 or throw new \InvalidArgumentException(
            'Timeout must be greater than 0 or `null`.',
        );

        return new Autowire(self::class, [$timeout]);
    }

    public function intercept(CallContextInterface $context, HandlerInterface $handler): mixed
    {
        $options = Helper::getOptions($context);
        if ($this->timeout === null) {
            unset($options['timeout']);
        } else {
            $options['timeout'] = [$this->timeout];
        }

        return $handler->handle(Helper::withOptions($context, $options));
    }
}