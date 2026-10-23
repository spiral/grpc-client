<?php

declare(strict_types=1);

namespace Spiral\Grpc\Client\Tests\Unit\Internal\ServiceClient;

use Google\Protobuf\GPBEmpty;
use Spiral\Grpc\Client\Config\ConnectionConfig;
use Spiral\Grpc\Client\Config\ServiceConfig;
use Spiral\Grpc\Client\Interceptor\Helper;
use Spiral\Grpc\Client\Internal\ServiceClient\ServiceClientTrait;
use Spiral\Grpc\Client\Internal\ServiceClient\ServiceConfigMap;
use Spiral\Grpc\Client\Tests\Acceptance\GrpcContext;
use Spiral\Grpc\Client\Tests\Unit\Fixture\CallContextFactory;
use Spiral\Grpc\Client\Tests\Unit\Fixture\FakeConnection;
use Spiral\Grpc\Client\Tests\Unit\Fixture\RecordingHandler;
use Spiral\Grpc\Client\Tests\Unit\Fixture\TestServiceInterface;
use Testo\Assert;
use Testo\Codecov\Covers;
use Testo\Test;

#[Test]
#[Covers(ServiceClientTrait::class)]
final class ServiceClientTraitTest
{
    public function callContextCarriesTheRequest(): void
    {
        $connections = [new FakeConnection(), new FakeConnection(), new FakeConnection()];
        $service = new ServiceConfig(new ConnectionConfig('127.0.0.1:1'));
        $in = new GPBEmpty();
        $handler = new RecordingHandler(static fn(): GPBEmpty => new GPBEmpty());
        $ctx = (new GrpcContext())
            ->withValue('metadata', ['x-request-id' => ['42']])
            ->withValue('options', ['timeout' => 500]);

        self::client($handler, $connections, CallContextFactory::serviceConfigs([$connections[0], $service]))
            ->Ping($ctx, $in);

        $context = $handler->contexts[0];
        Assert::same($context->getArguments(), [
            $connections[0],
            '/test.Service/Ping',
            $in,
            [GPBEmpty::class, 'decode'],
            ['x-request-id' => ['42']],
            ['timeout' => 500],
        ]);
        Assert::same(Helper::getConnections($context), $connections);
        Assert::same(Helper::getServiceConfig($context), $service);
        Assert::null(Helper::getServiceConfig(Helper::withCurrentConnection($context, $connections[1])));
    }

    public function missingMetadataAndOptionsBecomeEmptyArrays(): void
    {
        $handler = new RecordingHandler(static fn(): GPBEmpty => new GPBEmpty());

        self::client($handler, [new FakeConnection()], new ServiceConfigMap())->Ping(new GrpcContext(), new GPBEmpty());

        $context = $handler->contexts[0];
        Assert::same(Helper::getMetadata($context), []);
        Assert::same(Helper::getOptions($context), []);
    }

    /**
     * @param list<FakeConnection> $connections
     */
    private static function client(
        RecordingHandler $handler,
        array $connections,
        ServiceConfigMap $serviceConfigs,
    ): TestServiceInterface {
        return new class($handler, $connections, $serviceConfigs) implements TestServiceInterface {
            use ServiceClientTrait;

            public function Ping(\Spiral\RoadRunner\GRPC\ContextInterface $ctx, GPBEmpty $in): GPBEmpty
            {
                return $this->_handle(__FUNCTION__, $ctx, $in, GPBEmpty::class);
            }
        };
    }
}
