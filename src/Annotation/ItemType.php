<?php

declare(strict_types=1);

namespace Sunrise\Symfony\OpenApi\Annotation;

use Attribute;
use Sunrise\Hydrator\Annotation\Subtype;

#[Attribute(Attribute::TARGET_PROPERTY)]
final class ItemType extends Subtype
{
}
