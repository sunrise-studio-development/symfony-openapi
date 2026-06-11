<?php

declare(strict_types=1);

namespace Sunrise\Symfony\OpenApi\Tests\Fixture;

final readonly class DtoFixture
{
    public function __construct(
        public string $foo = 'bar',
    ) {
    }
}
