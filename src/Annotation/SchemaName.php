<?php

declare(strict_types=1);

namespace Sunrise\Symfony\OpenApi\Annotation;

use Attribute;
use Override;
use Sunrise\Http\Router\OpenApi\Annotation\SchemaNameInterface;

#[Attribute(Attribute::TARGET_CLASS)]
final readonly class SchemaName implements SchemaNameInterface
{
    public function __construct(
        private string $name,
    ) {
    }

    #[Override]
    public function getSchemaName(): string
    {
        return $this->name;
    }
}
