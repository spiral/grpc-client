<?php

declare(strict_types=1);

namespace Spiral\Grpc\Client\Tests\Testo;

use Internal\Container\Container;
use Testo\Common\EventListenerCollector;
use Testo\Common\PluginConfigurator;
use Testo\Event\TestSuite\TestSuiteFinished;
use Testo\Event\TestSuite\TestSuiteStarting;

/**
 * Testo plugin that manages the grpcbin server lifecycle.
 *
 * Starts the grpcbin binary when the test suite begins and stops it when the suite finishes.
 * The plugin should be attached to a specific suite via {@see SuitePlugins::with()}.
 *
 * @see https://github.com/moul/grpcbin
 */
final class GrpcBinPlugin implements PluginConfigurator
{
    /** @var resource|null grpcbin process handle */
    private $process = null;

    /**
     * @param non-empty-string $binary Absolute path to the grpcbin executable.
     * @param non-empty-string $address Host and port for the insecure gRPC listener (e.g. "127.0.0.1:9000").
     * @param non-empty-string $tlsCert Path to the TLS certificate file (required by grpcbin even if not used).
     * @param non-empty-string $tlsKey Path to the TLS private key file.
     */
    public function __construct(
        private readonly string $binary,
        private readonly string $address = '127.0.0.1:9000',
        private readonly string $tlsCert = '',
        private readonly string $tlsKey = '',
    ) {}

    #[\Override]
    public function configure(Container $container): void
    {
        $listeners = $container->get(EventListenerCollector::class);

        $listeners->addListener(TestSuiteStarting::class, $this->start(...));
        $listeners->addListener(TestSuiteFinished::class, $this->stop(...));
    }

    /**
     * Start the grpcbin server as a background process.
     */
    private function start(TestSuiteStarting $event): void
    {
        if ($this->process !== null) {
            return;
        }

        if (!\file_exists($this->binary)) {
            throw new \RuntimeException("grpcbin binary not found at: {$this->binary}");
        }

        $command = \sprintf(
            '%s -insecure-addr %s -tls-cert %s -tls-key %s',
            \escapeshellarg($this->binary),
            \escapeshellarg($this->address),
            \escapeshellarg($this->tlsCert),
            \escapeshellarg($this->tlsKey),
        );

        $descriptors = [
            0 => ['pipe', 'r'], // stdin
            1 => ['pipe', 'w'], // stdout
            2 => ['pipe', 'w'], // stderr
        ];

        $this->process = \proc_open($command, $descriptors, $pipes);

        if (!\is_resource($this->process)) {
            throw new \RuntimeException('Failed to start grpcbin process');
        }

        $this->waitForReady();
    }

    /**
     * Stop the grpcbin server when the suite finishes.
     */
    private function stop(TestSuiteFinished $event): void
    {
        $this->killProcess();
    }

    /**
     * Terminate the grpcbin process.
     *
     * On Windows, uses `taskkill` to kill the process tree.
     * On Unix, sends SIGTERM.
     */
    private function killProcess(): void
    {
        if ($this->process === null) {
            return;
        }

        $status = \proc_get_status($this->process);
        if ($status['running']) {
            if (\DIRECTORY_SEPARATOR === '\\') {
                \exec(\sprintf('taskkill /F /T /PID %d 2>NUL', $status['pid']));
            } else {
                \proc_terminate($this->process, 15);
            }
        }

        \proc_close($this->process);
        $this->process = null;
    }

    /**
     * Poll the server address until it accepts TCP connections (up to 5 seconds).
     */
    private function waitForReady(): void
    {
        [$host, $port] = \explode(':', $this->address);

        $deadline = \microtime(true) + 5.0;
        while (\microtime(true) < $deadline) {
            $socket = @\fsockopen($host, (int) $port, $errno, $errstr, 0.1);
            if ($socket !== false) {
                \fclose($socket);
                return;
            }
            \usleep(50_000); // 50ms between attempts
        }

        $this->killProcess();
        throw new \RuntimeException("grpcbin did not start within 5 seconds on {$this->address}");
    }
}
