<?php

declare(strict_types=1);

namespace Spiral\Grpc\Client\Internal\Connection;

interface ConnectionInterface
{
    /**
     * Check if the connection is established and ready to use.
     */
    public function isConnected(): bool;

    /**
     * Close the connection to the server.
     */
    public function disconnect(): void;

    /**
     * Establish a connection to the server.
     *
     * @param float $timeout The maximum time to wait for the connection to be established in seconds.
     * @throws \RuntimeException If the connection cannot be established within the specified timeout or
     *         an error occurs during the connection process.
     */
    public function connect(float $timeout): void;

    /**
     * Get the service client stub to make RPC calls.
     *
     * @internal Do not use this method directly.
     */
    public function getStub(): ClientStub;
}
