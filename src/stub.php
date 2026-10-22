<?php

declare(strict_types=1);

namespace Spiral\RoadRunner\GRPC;

if (!\interface_exists(ServiceInterface::class, true)) {
    /**
     * Indicates that given class expected to be GRPC service.
     *
     * @psalm-suppress UnrecognizedStatement Psalm does not analyse conditionally declared interfaces
     */
    interface ServiceInterface {}
}

if (!\interface_exists(ContextInterface::class, true)) {
    /**
     * Carries information about call context, client information and metadata.
     *
     * @psalm-type TValues = array<string, mixed>
     * @psalm-suppress UnrecognizedStatement Psalm does not analyse conditionally declared interfaces
     */
    interface ContextInterface
    {
        /**
         * Create context with new value.
         *
         * @param non-empty-string $key
         * @return $this
         */
        public function withValue(string $key, mixed $value): self;

        /**
         * Get context value or return null.
         *
         * @param non-empty-string $key
         */
        public function getValue(string $key): mixed;

        /**
         * Return all context values.
         *
         * @return TValues
         */
        public function getValues(): array;
    }
}
