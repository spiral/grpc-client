<?php

declare(strict_types=1);

use Spiral\Grpc\Client\Tests\Testo\GrpcBinPlugin;
use Testo\Application\Config\ApplicationConfig;
use Testo\Application\Config\FinderConfig;
use Testo\Application\Config\Plugin\SuitePlugins;
use Testo\Application\Config\SuiteConfig;

$projectRoot = __DIR__;
$isWindows = \DIRECTORY_SEPARATOR === '\\';
$grpcbinBinary = $projectRoot . '/grpcbin' . ($isWindows ? '.exe' : '');

return new ApplicationConfig(
    suites: [
        new SuiteConfig(
            name: 'SRC',
            location: new FinderConfig(
                include: ['src'],
            ),
        ),
        new SuiteConfig(
            name: 'Unit',
            location: new FinderConfig(
                include: ['tests/Unit'],
            ),
        ),
        new SuiteConfig(
            name: 'Acceptance',
            location: new FinderConfig(
                include: ['tests/Acceptance'],
            ),
            plugins: SuitePlugins::with(
                new GrpcBinPlugin(
                    binary: $grpcbinBinary,
                    address: '127.0.0.1:9000',
                    tlsCert: $projectRoot . '/tests/cert/server.crt',
                    tlsKey: $projectRoot . '/tests/cert/server.key',
                ),
            ),
        ),
    ],
);
