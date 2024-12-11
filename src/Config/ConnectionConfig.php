<?php

declare(strict_types=1);

namespace Spiral\Grpc\Client\Config;

final class ConnectionConfig
{
    /**
     * @param non-empty-string $address Address of the Temporal service.
     * @param TlsConfig|null $tls TLS configuration for the connection.
     *        If null provided, the connection is insecure.
     */
    public function __construct(
        public readonly string $address,
        public readonly ?TlsConfig $tls = null,
    ) {}

    /**
     * Set the TLS configuration for the connection.
     *
     * @param non-empty-string|null $rootCerts Root certificates string or file in PEM format.
     *         If null provided, default gRPC root certificates are used.
     * @param non-empty-string|null $privateKey Client private key string or file in PEM format.
     * @param non-empty-string|null $certChain Client certificate chain string or file in PEM format.
     * @param non-empty-string|null $serverName Server name override for TLS verification.
     */
    public function withTls(
        ?string $rootCerts = null,
        ?string $privateKey = null,
        ?string $certChain = null,
        ?string $serverName = null,
    ): self {
        return new self(
            $this->address,
            new TlsConfig($rootCerts, $privateKey, $certChain, $serverName),
        );
    }

    /**
     * Check if the connection is secure.
     *
     * @psalm-assert-if-true TlsConfig $this->tls
     * @psalm-assert-if-false null $this->tls
     */
    public function isSecure(): bool
    {
        return $this->tls !== null;
    }
}
