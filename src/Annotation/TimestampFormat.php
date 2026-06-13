<?php

declare(strict_types=1);

namespace Sunrise\Symfony\OpenApi\Annotation;

use Attribute;
use Sunrise\Http\Router\OpenApi\Annotation\TimestampFormatInterface;

#[Attribute(Attribute::TARGET_PROPERTY)]
final readonly class TimestampFormat implements TimestampFormatInterface
{
    public function __construct(
        public string $format,
    ) {
    }

    public function getTimestampFormat(): string
    {
        return $this->format;
    }
}
