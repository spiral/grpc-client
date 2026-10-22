<?php

declare(strict_types=1);

namespace Spiral\Grpc\Client\Tests\Unit\Fixture;

use Spiral\Interceptors\Context\CallContextInterface;
use Spiral\Interceptors\HandlerInterface;

/**
 * Handler that records every context it receives and delegates the result to a callback.
 */
final class RecordingHandler implements HandlerInterface
{
    /** @var list<CallContextInterface> */
    public array $contexts = [];

    /**
     * @param \Closure(CallContextInterface, int<1, max>): mixed $callback Receives the context and the call number.
     */
    public function __construct(
        private readonly \Closure $callback,
    ) {}

    public function handle(CallContextInterface $context): mixed
    {
        $this->contexts[] = $context;
        return ($this->callback)($context, \count($this->contexts));
    }

    public function calls(): int
    {
        return \count($this->contexts);
    }
}
