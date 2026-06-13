<?php

declare(strict_types=1);

namespace Sunrise\Symfony\OpenApi\Annotation;

use Attribute;
use Override;
use Sunrise\Http\Router\OpenApi\Annotation\PropertyNameInterface;

#[Attribute(Attribute::TARGET_PROPERTY)]
final readonly class PropertyName implements PropertyNameInterface
{
    public function __construct(
        private string $name,
    ) {
    }

    #[Override]
    public function getPropertyName(): string
    {
        return $this->name;
    }
}
