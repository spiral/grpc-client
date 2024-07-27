<?php

declare(strict_types=1);

namespace Spiral\Grpc\Client\Internal\ServiceClient;

use Google\Protobuf\Internal\Message;
use Spiral\Grpc\Client\Interceptor\Helper;
use Spiral\Grpc\Client\Internal\Connection\Connection;
use Spiral\Interceptors\Context\CallContext;
use Spiral\Interceptors\Context\Target;
use Spiral\Interceptors\HandlerInterface;
use Spiral\RoadRunner\GRPC\ContextInterface as RRGrpcContext;

/**
 * @see ClassGenerator::generate()
 *
 * @mixin \Spiral\RoadRunner\GRPC\ServiceInterface
 */
trait ServiceClientTrait
{
    public function __construct(
        private readonly Connection $connection,
        private readonly HandlerInterface $handler,
    ) {}

    /**
     * @param non-empty-string $function
     * @param non-empty-string $returnType
     * @throws \Throwable
     */
    private function _callAction(
        string $function,
        RRGrpcContext $ctx,
        Message $in,
        string $returnType,
    ): Message {
        $uri = '/' . static::NAME . '/' . $function;

        // todo add timeout
        $options = [];

        /** @see \Spiral\Grpc\Client\Internal\Connection\ClientStub::invoke() */
        $callContext = new CallContext(
            Target::fromClosure(fn(
                string $method,
                Message $in,
                callable $deserializer,
                array $metadata,
                array $options,
            ): mixed => $this->connection->getStub()->invoke($method, $in, $deserializer, $metadata, $options)),
            arguments: [
                # gRPC method
                $uri,
                # Message
                $in,
                # Deserializer
                [$returnType, 'decode'],
                # Metadata
                (array) $ctx->getValue('metadata'),
                # Options
                $options,
            ],
        );
        $callContext = Helper::withConnection($callContext, $this->connection);


        return $this->handler->handle($callContext);
    }
}
