<?php

declare(strict_types=1);

namespace Sunrise\Symfony\OpenApi;

final class RouteMetadata
{
    public function __construct(
        /** @var array<array-key, string> */
        public readonly array $tags,
        public readonly string $summary,
        public readonly string $description,
        public readonly bool $isDeprecated,
        public readonly bool $isApi,
    ) {
    }
}
