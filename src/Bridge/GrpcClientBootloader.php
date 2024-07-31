<?php

declare(strict_types=1);

namespace Spiral\Grpc\Client\Bridge;

use Spiral\Boot\Bootloader\Bootloader;
use Spiral\Core\BinderInterface;
use Spiral\Grpc\Client\ServiceClientProvider;

class GrpcClientBootloader extends Bootloader
{
    public function boot(BinderInterface $binder): void
    {
        // Define implementations for service client interfaces
        //
        /** @var ServiceClientProvider $provider */
        $provider = $binder->get(ServiceClientProvider::class);
        foreach ($provider->getClientInterfaces() as $interface) {
            $binder->bindSingleton($interface, static fn() => $provider->getServiceClient($interface));
        }
    }
}
