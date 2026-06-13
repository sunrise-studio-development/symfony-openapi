<?php

declare(strict_types=1);

namespace Sunrise\Symfony\OpenApi\Tests\PhpTypeSchemaResolver;

use PHPUnit\Framework\TestCase;
use Sunrise\Http\Router\OpenApi\Exception\UnsupportedPhpTypeException;
use Sunrise\Http\Router\OpenApi\PhpTypeSchemaResolver\TimestampPhpTypeSchemaResolver
    as SunriseTimestampPhpTypeSchemaResolver;
use Sunrise\Http\Router\OpenApi\Type;
use Sunrise\Http\Router\OpenApi\TypeFactory;
use Sunrise\Symfony\OpenApi\PhpTypeSchemaResolver\TimestampPhpTypeSchemaResolver;
use Sunrise\Symfony\OpenApi\Tests\TestKit;

final class TimestampPhpTypeSchemaResolverTest extends TestCase
{
    use TestKit;

    public function testMapDateTimeFormat(): void
    {
        $resolver = new TimestampPhpTypeSchemaResolver(new SunriseTimestampPhpTypeSchemaResolver());
        $resolver->setOpenApiConfiguration(self::createOpenApiConfiguration());

        $parameter = self::getParameterReflection('timestamp', 'createdAt');
        $type = TypeFactory::fromPhpTypeReflection($parameter->getType());
        $schema = $resolver->resolvePhpTypeSchema($type, $parameter);

        self::assertSame('1970-01-01', $schema['example']);
    }

    public function testDisabledMapDateTime(): void
    {
        $resolver = new TimestampPhpTypeSchemaResolver(new SunriseTimestampPhpTypeSchemaResolver());
        $resolver->setOpenApiConfiguration(self::createOpenApiConfiguration(defaultTimestampFormat: 'd.m.Y'));

        $parameter = self::getParameterReflection('disabledTimestamp', 'createdAt');
        $type = TypeFactory::fromPhpTypeReflection($parameter->getType());
        $schema = $resolver->resolvePhpTypeSchema($type, $parameter);

        self::assertSame('01.01.1970', $schema['example']);
    }

    public function testDefaultFormat(): void
    {
        $resolver = new TimestampPhpTypeSchemaResolver(new SunriseTimestampPhpTypeSchemaResolver());
        $resolver->setOpenApiConfiguration(self::createOpenApiConfiguration(defaultTimestampFormat: 'd.m.Y'));

        $parameter = self::getParameterReflection('defaultTimestamp', 'createdAt');
        $type = TypeFactory::fromPhpTypeReflection($parameter->getType());
        $schema = $resolver->resolvePhpTypeSchema($type, $parameter);

        self::assertSame('01.01.1970', $schema['example']);
    }

    public function testUnsupportedPhpType(): void
    {
        $this->expectException(UnsupportedPhpTypeException::class);

        $resolver = new TimestampPhpTypeSchemaResolver(new SunriseTimestampPhpTypeSchemaResolver());
        $resolver->setOpenApiConfiguration(self::createOpenApiConfiguration());
        $resolver->resolvePhpTypeSchema(
            new Type('string'),
            self::getParameterReflection('defaultTimestamp', 'createdAt'),
        );
    }

    public function testWeight(): void
    {
        self::assertSame(
            new SunriseTimestampPhpTypeSchemaResolver()->getWeight(),
            new TimestampPhpTypeSchemaResolver(new SunriseTimestampPhpTypeSchemaResolver())->getWeight(),
        );
    }
}
