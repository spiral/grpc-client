<?php

declare(strict_types=1);

namespace Spiral\Grpc\Client\Internal\Connection;

use Spiral\Grpc\Client\Config\ConnectionConfig;

/**
 * @internal
 */
final class Connection implements ConnectionInterface
{
    private ClientStub $stub;

    /**
     * True if Stub wasn't created yet
     */
    private bool $closed = true;

    public function __construct(
        public readonly ConnectionConfig $config,
    ) {
        \extension_loaded('grpc') or throw new \RuntimeException('The gRPC extension is required.');
        $this->initClient();
    }

    public function getStub(): ClientStub
    {
        $this->initClient();
        return $this->stub;
    }

    public function isConnected(): bool
    {
        return ConnectionState::from($this->stub->getConnectivityState(false)) === ConnectionState::Ready;
    }

    public function connect(float $timeout): void
    {
        $deadline = \microtime(true) + $timeout;
        $this->initClient();

        try {
            if ($this->isConnected()) {
                return;
            }
        } catch (\RuntimeException) {
            $this->disconnect();
            $this->initClient();
        }

        // Start connecting
        $this->getState(true);
        $isFiber = \Fiber::getCurrent() !== null;
        do {
            // Wait a bit
            $isFiber
                ? \Fiber::suspend()
                : $this->stub->waitForReady(50);

            $alive = \microtime(true) < $deadline;
            $state = $this->getState();
        } while ($alive && $state === ConnectionState::Connecting);

        $alive or throw new \RuntimeException('Failed to connect to the service. Timeout exceeded.');
        $state === ConnectionState::Idle and throw new \RuntimeException(
            'Failed to connect to the service. Channel is in idle state.',
        );
        $state === ConnectionState::TransientFailure and throw new \RuntimeException(
            'Failed to connect to the service. Channel is in transient failure state.',
        );
        $state === ConnectionState::Shutdown and throw new \RuntimeException(
            'Failed to connect to the service. Channel is in shutdown state.',
        );
    }

    public function disconnect(): void
    {
        if ($this->closed) {
            return;
        }

        $this->closed = true;
        $this->stub->close();
    }

    public function __destruct()
    {
        $this->disconnect();
    }

    private static function loadCert(?string $cert): ?string
    {
        return match (true) {
            $cert === null, $cert === '' => null,
            \is_file($cert) => false === ($content = \file_get_contents($cert))
                ? throw new \InvalidArgumentException("Failed to load certificate from file `$cert`.")
                : $content,
            default => $cert,
        };
    }

    private function getState(bool $tryToConnect = false): ConnectionState
    {
        return ConnectionState::from($this->stub->getConnectivityState($tryToConnect));
    }

    /**
     * Create a new Stub with a new channel
     */
    private function initClient(): void
    {
        if (!$this->closed) {
            return;
        }

        $options = $this->config->isSecure()
            ? [
                'credentials' => \Grpc\ChannelCredentials::createSsl(
                    self::loadCert($this->config->rootCerts),
                    self::loadCert($this->config->privateKey),
                    self::loadCert($this->config->certChain),
                ),
            ]
            : ['credentials' => \Grpc\ChannelCredentials::createInsecure()];

        $this->stub = new ClientStub($this->config->address, $options);
        $this->closed = false;
    }

    /**
     * Wait for the channel to be ready.
     *
     * @param float $timeout in seconds
     *
     * @return bool true if channel is ready
     * @throws \Exception if channel is in FATAL_ERROR state
     */
    private function waitForReady(float $timeout): bool
    {
        /** @psalm-suppress InvalidOperand */
        return $this->stub->waitForReady((int) ($timeout * 1_000_000));
    }
}
