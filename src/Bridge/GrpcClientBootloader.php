<?php

declare(strict_types=1);

namespace Spiral\Grpc\Client\Bridge;

use Spiral\Boot\Bootloader\Bootloader;
use Spiral\Core\BinderInterface;
use Spiral\Grpc\Client\ServiceClientProvider;

final class GrpcClientBootloader extends Bootloader
{
    public function boot(BinderInterface $binder, ServiceClientProvider $provider): void
    {
        // Define implementations for service client interfaces
        foreach ($provider->getClientInterfaces() as $interface) {
            $binder->bindSingleton($interface, static fn() => $provider->getServiceClient($interface));
        }
    }
}
