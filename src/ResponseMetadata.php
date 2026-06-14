<?php

declare(strict_types=1);

namespace Sunrise\Symfony\OpenApi;

final readonly class ResponseMetadata
{
    public function __construct(
        public ?int $code,
        /** @var array<array-key, string> */
        public array $formats,
    ) {
    }
}
