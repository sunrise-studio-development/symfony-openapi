<?php

declare(strict_types=1);

namespace Sunrise\Symfony\OpenApi;

/**
 * @since 1.0.0
 */
final readonly class ResponseMetadata
{
    public function __construct(
        public ?int $status,
        /** @var array<array-key, string> */
        public array $formats,
    ) {
    }
}
