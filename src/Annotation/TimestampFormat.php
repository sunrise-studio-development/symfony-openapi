<?php

declare(strict_types=1);

namespace Sunrise\Symfony\OpenApi\Annotation;

use Attribute;
use Override;
use Sunrise\Http\Router\OpenApi\Annotation\TimestampFormatInterface;

#[Attribute(Attribute::TARGET_PROPERTY)]
final class TimestampFormat implements TimestampFormatInterface
{
    public function __construct(
        public readonly string $format,
    ) {
    }

    #[Override]
    public function getTimestampFormat(): string
    {
        return $this->format;
    }
}
