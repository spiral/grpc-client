<?php

declare(strict_types=1);

namespace Spiral\Grpc\Client\Tests\Acceptance;

use Spiral\Grpc\Client\Config\ConnectionConfig;
use Spiral\Grpc\Client\Exception\ServiceClientException;
use Spiral\Grpc\Client\GrpcClient;
use Spiral\Grpc\Client\Interceptor\ConnectionsRotationInterceptor;
use Spiral\Grpc\Client\Interceptor\RetryInterceptor;
use Spiral\Grpc\Client\Interceptor\SetTimeoutInterceptor;
use Spiral\Grpc\Client\Tests\Generated\Grpcbin\DummyMessage;
use Spiral\Grpc\Client\Tests\Generated\Grpcbin\EmptyMessage;
use Spiral\Grpc\Client\Tests\Generated\Grpcbin\GRPCBinInterface;
use Spiral\Grpc\Client\Tests\Generated\Grpcbin\SpecificErrorRequest;
use Testo\Assert;
use Testo\Expect;
use Testo\Test;

#[Test]
final class GrpcClientTest
{
    private const GRPCBIN_ADDRESS = '127.0.0.1:9000';
    private const GRPCBIN_TLS_PORT = 9001;
    private const TLS_CERT = __DIR__ . '/../cert/server.crt';

    public function basicUnaryCall(): void
    {
        $client = GrpcClient::create(self::GRPCBIN_ADDRESS);
        $service = $client->service(GRPCBinInterface::class);
        $ctx = new GrpcContext();

        $request = new DummyMessage([
            'f_string' => 'hello standalone',
            'f_int32' => 7,
        ]);

        $response = $service->DummyUnary($ctx, $request);

        Assert::same($response->getFString(), 'hello standalone');
        Assert::same($response->getFInt32(), 7);
    }

    public function unaryCallWithInterceptors(): void
    {
        $client = GrpcClient::create(self::GRPCBIN_ADDRESS)
            ->withInterceptors([
                SetTimeoutInterceptor::createConfig(10_000),
                RetryInterceptor::createConfig(maximumAttempts: 3),
            ]);

        $service = $client->service(GRPCBinInterface::class);
        $ctx = new GrpcContext();

        $response = $service->Empty($ctx, new EmptyMessage());

        Assert::instanceOf($response, EmptyMessage::class);
    }

    public function connectionsRotationFailover(): void
    {
        // First endpoint is dead, second is alive — rotation should switch to the working one
        $client = GrpcClient::create(['127.0.0.1:19999', self::GRPCBIN_ADDRESS])
            ->withInterceptors([
                new ConnectionsRotationInterceptor(),
            ]);

        $service = $client->service(GRPCBinInterface::class);
        $ctx = new GrpcContext();

        $response = $service->DummyUnary($ctx, new DummyMessage([
            'f_string' => 'rotated',
        ]));

        Assert::same($response->getFString(), 'rotated');
    }

    public function tlsUnaryCall(): void
    {
        $client = GrpcClient::create(
            (new ConnectionConfig('localhost:' . self::GRPCBIN_TLS_PORT))->withTls(rootCerts: self::TLS_CERT),
        )->withInterceptors([SetTimeoutInterceptor::createConfig(5_000)]);
        $service = $client->service(GRPCBinInterface::class);

        $response = $service->DummyUnary(new GrpcContext(), new DummyMessage(['f_string' => 'secure']));

        Assert::same($response->getFString(), 'secure');
    }

    public function tlsServerNameOverride(): void
    {
        // The certificate is issued for `localhost`, so a call by IP passes verification only with the override
        $client = GrpcClient::create(
            (new ConnectionConfig('127.0.0.1:' . self::GRPCBIN_TLS_PORT))
                ->withTls(rootCerts: self::TLS_CERT, serverName: 'localhost'),
        )->withInterceptors([SetTimeoutInterceptor::createConfig(5_000)]);
        $service = $client->service(GRPCBinInterface::class);

        $response = $service->DummyUnary(new GrpcContext(), new DummyMessage(['f_string' => 'overridden']));

        Assert::same($response->getFString(), 'overridden');
    }

    public function specificErrorNotFound(): void
    {
        $client = GrpcClient::create(self::GRPCBIN_ADDRESS);
        $service = $client->service(GRPCBinInterface::class);

        Expect::exception(ServiceClientException::class);

        $service->SpecificError(new GrpcContext(), new SpecificErrorRequest([
            'code' => 5, // NOT_FOUND
            'reason' => 'test not found',
        ]));
    }

    public function specificErrorPermissionDenied(): void
    {
        $client = GrpcClient::create(self::GRPCBIN_ADDRESS);
        $service = $client->service(GRPCBinInterface::class);

        Expect::exception(ServiceClientException::class);

        $service->SpecificError(new GrpcContext(), new SpecificErrorRequest([
            'code' => 7, // PERMISSION_DENIED
            'reason' => 'test permission denied',
        ]));
    }
}
