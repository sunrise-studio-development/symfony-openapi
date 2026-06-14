<?php

declare(strict_types=1);

namespace Sunrise\Symfony\OpenApi\Tests\Fixture;

final class DtoFixture
{
    public function __construct(
        public readonly string $foo = 'bar',
    ) {
    }
}
