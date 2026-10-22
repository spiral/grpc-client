<?php

declare(strict_types=1);

namespace Spiral\Grpc\Client\Tests\Unit\Config;

use Spiral\Grpc\Client\Config\ConnectionConfig;
use Spiral\Grpc\Client\Config\TlsConfig;
use Testo\Assert;
use Testo\Codecov\Covers;
use Testo\Test;

#[Test]
#[Covers(ConnectionConfig::class)]
#[Covers(TlsConfig::class)]
final class ConnectionConfigTest
{
    public function connectionIsInsecureByDefault(): void
    {
        $config = new ConnectionConfig('localhost:9000');

        Assert::false($config->isSecure());
        Assert::null($config->tls);
    }

    public function withTlsReturnsSecureCopy(): void
    {
        $config = new ConnectionConfig('localhost:9000');

        $secure = $config->withTls('root.crt', 'client.key', 'client.crt', 'example.com');

        Assert::notSame($secure, $config);
        Assert::false($config->isSecure());
        Assert::true($secure->isSecure());
        Assert::same($secure->address, 'localhost:9000');
        Assert::same($secure->tls->rootCerts, 'root.crt');
        Assert::same($secure->tls->privateKey, 'client.key');
        Assert::same($secure->tls->certChain, 'client.crt');
        Assert::same($secure->tls->serverName, 'example.com');
    }

    public function withTlsWithoutArgumentsUsesDefaultRoots(): void
    {
        $secure = (new ConnectionConfig('localhost:9000'))->withTls();

        Assert::true($secure->isSecure());
        Assert::null($secure->tls->rootCerts);
        Assert::null($secure->tls->privateKey);
        Assert::null($secure->tls->certChain);
        Assert::null($secure->tls->serverName);
    }
}
