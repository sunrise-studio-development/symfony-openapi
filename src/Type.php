<?php

declare(strict_types=1);

namespace Sunrise\Symfony\OpenApi;

use Override;
use Sunrise\Http\Router\OpenApi\TypeInterface;

final readonly class Type implements TypeInterface
{
    public function __construct(
        private string $name,
        private bool $allowsNull = false,
    ) {
    }

    #[Override]
    public function getName(): string
    {
        return $this->name;
    }

    #[Override]
    public function allowsNull(): bool
    {
        return $this->allowsNull;
    }
}
