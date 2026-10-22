<?php

declare(strict_types=1);

namespace Spiral\Grpc\Client\Tests\Acceptance;

use Spiral\Grpc\Client\Config\ConnectionConfig;
use Spiral\Grpc\Client\GrpcClient;
use Spiral\Grpc\Client\Internal\Connection\Connection;
use Spiral\Grpc\Client\Tests\Generated\Grpcbin\DummyMessage;
use Spiral\Grpc\Client\Tests\Generated\Grpcbin\GRPCBinInterface;
use Testo\Assert;
use Testo\Codecov\Covers;
use Testo\Core\Exception\SkipTest;
use Testo\Skip;
use Testo\Test;

#[Test]
#[Covers(Connection::class)]
final class ConnectionTest
{
    private const INSECURE_ADDRESS = '127.0.0.1:9000';

    /** grpcbin serves TLS on its default `-tls-addr` with the certificate from `tests/cert` (CN=localhost). */
    private const TLS_PORT = 9001;

    public function connectWaitsUntilChannelIsReady(): void
    {
        $connection = new Connection(new ConnectionConfig(self::INSECURE_ADDRESS));

        $connection->connect(5.0);

        Assert::true($connection->isConnected());
        // Already connected: returns without waiting
        $connection->connect(0.0);
        Assert::true($connection->isConnected());
    }

    public function callOverTlsWithCustomRootCertificate(): void
    {
        \DIRECTORY_SEPARATOR === '\\' and throw new SkipTest(
            'On Windows ext-grpc segfaults at process shutdown once a TLS channel has been opened.',
        );

        $config = (new ConnectionConfig('localhost:' . self::TLS_PORT))
            ->withTls(rootCerts: \dirname(__DIR__) . '/cert/server.crt');

        $response = GrpcClient::create($config)
            ->service(GRPCBinInterface::class)
            ->DummyUnary(new GrpcContext(), new DummyMessage(['f_string' => 'secure']));

        Assert::same($response->getFString(), 'secure');
    }

    #[Skip('Bug: TlsConfig::$serverName is never passed to the channel (grpc.ssl_target_name_override).')]
    public function serverNameOverridesTlsTargetName(): void
    {
        $config = (new ConnectionConfig('127.0.0.1:' . self::TLS_PORT))
            ->withTls(rootCerts: \dirname(__DIR__) . '/cert/server.crt', serverName: 'localhost');

        $response = GrpcClient::create($config)
            ->service(GRPCBinInterface::class)
            ->DummyUnary(new GrpcContext(), new DummyMessage(['f_string' => 'secure']));

        Assert::same($response->getFString(), 'secure');
    }
}
