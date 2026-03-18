<?php

declare(strict_types=1);

namespace Spiral\Grpc\Client\Tests\Acceptance;

use Spiral\Core\FactoryInterface;
use Spiral\Grpc\Client\Config\ConnectionConfig;
use Spiral\Grpc\Client\Config\GrpcClientConfig;
use Spiral\Grpc\Client\Config\ServiceConfig;
use Spiral\Grpc\Client\Exception\ServiceClientException;
use Spiral\Grpc\Client\ServiceClientProvider;
use Spiral\Grpc\Client\Tests\Generated\Grpcbin\DummyMessage;
use Spiral\Grpc\Client\Tests\Generated\Grpcbin\EmptyMessage;
use Spiral\Grpc\Client\Tests\Generated\Grpcbin\GRPCBinInterface;
use Spiral\Grpc\Client\Tests\Generated\Grpcbin\HeadersMessage;
use Spiral\Grpc\Client\Tests\Generated\Grpcbin\SpecificErrorRequest;
use Spiral\RoadRunner\GRPC\ContextInterface;
use Testo\Assert;
use Testo\Expect;
use Testo\Test;

#[Test]
final class GrpcBinTest
{
    private const GRPCBIN_ADDRESS = '127.0.0.1:9000';

    public function dummyUnaryEcho(): void
    {
        $client = $this->createClient();
        $ctx = $this->createContext();

        $request = new DummyMessage([
            'f_string' => 'hello grpc',
            'f_int32' => 42,
            'f_bool' => true,
        ]);

        $response = $client->DummyUnary($ctx, $request);

        Assert::same($response->getFString(), 'hello grpc');
        Assert::same($response->getFInt32(), 42);
        Assert::true($response->getFBool());
    }

    public function dummyUnaryWithNestedMessage(): void
    {
        $client = $this->createClient();
        $ctx = $this->createContext();

        $sub = new DummyMessage\Sub(['f_string' => 'nested value']);
        $request = new DummyMessage([
            'f_string' => 'parent',
            'f_sub' => $sub,
        ]);

        $response = $client->DummyUnary($ctx, $request);

        Assert::same($response->getFString(), 'parent');
        Assert::notSame($response->getFSub(), null);
        Assert::same($response->getFSub()->getFString(), 'nested value');
    }

    public function dummyUnaryWithRepeatedFields(): void
    {
        $client = $this->createClient();
        $ctx = $this->createContext();

        $request = new DummyMessage([
            'f_strings' => ['one', 'two', 'three'],
            'f_int32s' => [1, 2, 3],
        ]);

        $response = $client->DummyUnary($ctx, $request);

        Assert::same(\iterator_to_array($response->getFStrings()), ['one', 'two', 'three']);
        Assert::same(\iterator_to_array($response->getFInt32S()), [1, 2, 3]);
    }

    public function emptyCall(): void
    {
        $client = $this->createClient();
        $ctx = $this->createContext();

        $response = $client->Empty($ctx, new EmptyMessage());

        Assert::instanceOf($response, EmptyMessage::class);
    }

    public function specificErrorNotFound(): void
    {
        $client = $this->createClient();
        $ctx = $this->createContext();

        Expect::exception(ServiceClientException::class);

        $client->SpecificError($ctx, new SpecificErrorRequest([
            'code' => 5, // NOT_FOUND
            'reason' => 'test not found error',
        ]));
    }

    public function specificErrorPermissionDenied(): void
    {
        $client = $this->createClient();
        $ctx = $this->createContext();

        Expect::exception(ServiceClientException::class);

        $client->SpecificError($ctx, new SpecificErrorRequest([
            'code' => 7, // PERMISSION_DENIED
            'reason' => 'test permission denied',
        ]));
    }

    public function headersUnary(): void
    {
        $client = $this->createClient();
        $ctx = $this->createContext();

        $response = $client->HeadersUnary($ctx, new EmptyMessage());

        // HeadersUnary returns the received metadata
        Assert::instanceOf($response, HeadersMessage::class);
    }

    public function indexReturnsEndpoints(): void
    {
        $client = $this->createClient();
        $ctx = $this->createContext();

        $response = $client->Index($ctx, new EmptyMessage());

        Assert::notSame($response->getDescription(), '');
        Assert::true($response->getEndpoints()->count() > 0);
    }

    private function createClient(): GRPCBinInterface
    {
        $config = new GrpcClientConfig(
            services: [
                new ServiceConfig(
                    connections: new ConnectionConfig(self::GRPCBIN_ADDRESS),
                    interfaces: [GRPCBinInterface::class],
                ),
            ],
        );

        $factory = new class implements FactoryInterface {
            public function make(string $alias, array $parameters = []): mixed
            {
                return new $alias(...$parameters);
            }
        };

        $provider = new ServiceClientProvider($config, $factory);

        return $provider->getServiceClient(GRPCBinInterface::class);
    }

    private function createContext(array $metadata = []): ContextInterface
    {
        return new GrpcContext(['metadata' => $metadata]);
    }
}
