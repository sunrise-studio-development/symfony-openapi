<?php

declare(strict_types=1);

namespace Sunrise\Symfony\OpenApi\Tests;

use PHPUnit\Framework\TestCase;
use Sunrise\Symfony\OpenApi\Type;

final class TypeTest extends TestCase
{
    public function testConstructor(): void
    {
        $type = new Type('Foo');

        self::assertSame('Foo', $type->getName());
        self::assertFalse($type->allowsNull());
    }

    public function testAllowsNull(): void
    {
        $type = new Type('Foo', allowsNull: true);

        self::assertTrue($type->allowsNull());
    }
}
