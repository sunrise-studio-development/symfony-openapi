<?php

declare(strict_types=1);

namespace Sunrise\Symfony\OpenApi;

/**
 * @since 1.0.0
 */
final readonly class RouteMetadata
{
    public function __construct(
        /** @var array<array-key, string> */
        public array $tags,
        public string $summary,
        public string $description,
        public bool $isDeprecated,
        public bool $isApi,
    ) {
    }
}
