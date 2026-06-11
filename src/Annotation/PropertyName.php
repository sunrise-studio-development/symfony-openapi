<?php

declare(strict_types=1);

namespace Sunrise\Symfony\OpenApi\Annotation;

use Attribute;
use Sunrise\Http\Router\OpenApi\Annotation\PropertyNameInterface;

/**
 * @since 1.0.0
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
final readonly class PropertyName implements PropertyNameInterface
{
    public function __construct(
        private string $name,
    ) {
    }

    public function getPropertyName(): string
    {
        return $this->name;
    }
}
