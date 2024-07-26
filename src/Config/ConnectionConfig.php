<?php

declare(strict_types=1);

namespace Spiral\Grpc\Client\Config;

final class ConnectionConfig
{
    private function __construct(
        public readonly string $address,
        public readonly bool $secure = false,
        public readonly ?string $rootCerts = null,
        public readonly ?string $privateKey = null,
        public readonly ?string $certChain = null,
    ) {}

    /**
     * @param non-empty-string|null $rootCerts Root certificates string or file in PEM format.
     *         If null provided, default gRPC root certificates are used.
     * @param non-empty-string|null $privateKey Client private key string or file in PEM format.
     * @param non-empty-string|null $certChain Client certificate chain string or file in PEM format.
     */
    public static function createSecure(
        string $address,
        ?string $rootCerts = null,
        ?string $privateKey = null,
        ?string $certChain = null,
    ): self {
        return new self($address, true, $rootCerts, $privateKey, $certChain);
    }

    public static function createInsecure(
        string $address,
    ): self {
        return new self($address);
    }

    public function isSecure(): bool
    {
        return $this->secure;
    }
}
