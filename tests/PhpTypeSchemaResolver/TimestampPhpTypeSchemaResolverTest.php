<?php

declare(strict_types=1);

namespace Sunrise\Symfony\OpenApi\Tests\PhpTypeSchemaResolver;

use PHPUnit\Framework\TestCase;
use ReflectionParameter;
use Sunrise\Http\Router\OpenApi\PhpTypeSchemaResolver\TimestampPhpTypeSchemaResolver
    as BaseTimestampPhpTypeSchemaResolver;
use Sunrise\Http\Router\OpenApi\TypeFactory;
use Sunrise\Symfony\OpenApi\PhpTypeSchemaResolver\TimestampPhpTypeSchemaResolver;
use Sunrise\Symfony\OpenApi\Tests\TestKit;

final class TimestampPhpTypeSchemaResolverTest extends TestCase
{
    use TestKit;

    public function testResolvePhpTypeSchemaWithMapDateTimeFormat(): void
    {
        $resolver = new TimestampPhpTypeSchemaResolver(new BaseTimestampPhpTypeSchemaResolver());
        $resolver->setOpenApiConfiguration(self::createOpenApiConfiguration());

        $parameter = self::getParameterReflection('timestamp', 'createdAt');
        $type = TypeFactory::fromPhpTypeReflection($parameter->getType());
        $schema = $resolver->resolvePhpTypeSchema($type, $parameter);

        self::assertSame('1970-01-01', $schema['example']);
    }

    public function testResolvePhpTypeSchemaWithDisabledMapDateTime(): void
    {
        $resolver = new TimestampPhpTypeSchemaResolver(new BaseTimestampPhpTypeSchemaResolver());
        $resolver->setOpenApiConfiguration(self::createOpenApiConfiguration(defaultTimestampFormat: 'd.m.Y'));

        $parameter = self::getParameterReflection('disabledTimestamp', 'createdAt');
        $type = TypeFactory::fromPhpTypeReflection($parameter->getType());
        $schema = $resolver->resolvePhpTypeSchema($type, $parameter);

        self::assertSame('01.01.1970', $schema['example']);
    }

    private static function getParameterReflection(string $method, string $parameter): ReflectionParameter
    {
        $controllerReflection = self::createControllerReflection($method);
        foreach ($controllerReflection->getParameters() as $parameterReflection) {
            if ($parameterReflection->name === $parameter) {
                return $parameterReflection;
            }
        }

        self::fail(\sprintf(
            'Parameter "%s::%s(%s)" was not found.',
            $controllerReflection::class,
            $method,
            $parameter,
        ));
    }
}
