<?php

declare(strict_types=1);

namespace Sunrise\Symfony\OpenApi\Annotation;

use Attribute;
use Sunrise\Http\Router\OpenApi\Annotation\IgnorePropertyInterface;

#[Attribute(Attribute::TARGET_PROPERTY)]
final class IgnoreProperty implements IgnorePropertyInterface
{
}
