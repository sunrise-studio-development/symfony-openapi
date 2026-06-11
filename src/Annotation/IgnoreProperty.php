<?php

declare(strict_types=1);

namespace Sunrise\Symfony\OpenApi\Annotation;

use Attribute;
use Sunrise\Http\Router\OpenApi\Annotation\IgnorePropertyInterface;

/**
 * @since 1.0.0
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
final readonly class IgnoreProperty implements IgnorePropertyInterface
{
}
