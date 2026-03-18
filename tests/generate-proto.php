<?php

declare(strict_types=1);

/**
 * Generates PHP classes from proto files using protoc and protoc-gen-php-grpc.
 *
 * Usage: php tests/generate-proto.php
 */

$root = \dirname(__DIR__);
$generatedDir = $root . '/tests/Generated';
$protoDir = $root . '/tests/proto';
$tempDir = $root . '/.proto-tmp';

$isWindows = \DIRECTORY_SEPARATOR === '\\';
$protocBin = $root . '/protoc' . ($isWindows ? '.exe' : '');
$pluginBin = $root . '/protoc-gen-php-grpc' . ($isWindows ? '.exe' : '');

// Validate binaries exist
if (!\file_exists($protocBin)) {
    \fwrite(\STDERR, "protoc binary not found at: {$protocBin}\nRun: composer update\n");
    exit(1);
}
if (!\file_exists($pluginBin)) {
    \fwrite(\STDERR, "protoc-gen-php-grpc binary not found at: {$pluginBin}\nRun: composer update\n");
    exit(1);
}

// Find proto files
$protoFiles = \glob($protoDir . '/*.proto');
if ($protoFiles === false || $protoFiles === []) {
    \fwrite(\STDERR, "No .proto files found in: {$protoDir}\n");
    exit(1);
}

// Clean generated directory
if (\is_dir($generatedDir)) {
    echo "Cleaning {$generatedDir}...\n";
    deleteDirectory($generatedDir);
}
\mkdir($generatedDir, 0755, true);

// Clean temp directory
if (\is_dir($tempDir)) {
    deleteDirectory($tempDir);
}
\mkdir($tempDir, 0755, true);

// Generate proto
$protoFilesList = \implode(' ', \array_map('escapeshellarg', $protoFiles));
$command = \sprintf(
    '%s --php_out=%s --php-grpc_out=%s --plugin=protoc-gen-php-grpc=%s --proto_path=%s %s',
    \escapeshellarg($protocBin),
    \escapeshellarg($tempDir),
    \escapeshellarg($tempDir),
    \escapeshellarg($pluginBin),
    \escapeshellarg($protoDir),
    $protoFilesList,
);

echo "Running: {$command}\n";
\passthru($command, $exitCode);

if ($exitCode !== 0) {
    \fwrite(\STDERR, "protoc failed with exit code {$exitCode}\n");
    deleteDirectory($tempDir);
    exit($exitCode);
}

// Move generated files from PSR-4 nested structure to the correct location
// protoc creates: <tempDir>/Spiral/Grpc/Client/Tests/Generated/<namespace>/
// We need:        tests/Generated/<namespace>/
$sourceDir = $tempDir . '/Spiral/Grpc/Client/Tests/Generated';
if (!\is_dir($sourceDir)) {
    \fwrite(\STDERR, "Expected generated directory not found: {$sourceDir}\n");
    deleteDirectory($tempDir);
    exit(1);
}

moveContents($sourceDir, $generatedDir);
deleteDirectory($tempDir);

echo "Proto generation complete. Files written to tests/Generated/\n";

// ---

function deleteDirectory(string $dir): void
{
    if (!\is_dir($dir)) {
        return;
    }
    $items = new \RecursiveIteratorIterator(
        new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS),
        \RecursiveIteratorIterator::CHILD_FIRST,
    );
    foreach ($items as $item) {
        $item->isDir() ? \rmdir($item->getPathname()) : \unlink($item->getPathname());
    }
    \rmdir($dir);
}

function moveContents(string $source, string $destination): void
{
    $items = new \RecursiveIteratorIterator(
        new \RecursiveDirectoryIterator($source, \RecursiveDirectoryIterator::SKIP_DOTS),
        \RecursiveIteratorIterator::SELF_FIRST,
    );
    foreach ($items as $item) {
        $target = $destination . '/' . \substr($item->getPathname(), \strlen($source) + 1);
        if ($item->isDir()) {
            if (!\is_dir($target)) {
                \mkdir($target, 0755, true);
            }
        } else {
            \rename($item->getPathname(), $target);
        }
    }
}
