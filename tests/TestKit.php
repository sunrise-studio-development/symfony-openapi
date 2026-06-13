<?php

declare(strict_types=1);

namespace Sunrise\Symfony\OpenApi\Tests;

use ReflectionMethod;
use ReflectionParameter;
use Sunrise\Coder\Dictionary\MediaType;
use Sunrise\Http\Router\OpenApi\OpenApiConfiguration;
use Sunrise\Http\Router\OpenApi\OpenApiPhpTypeSchemaResolverManagerInterface;
use Sunrise\Http\Router\OpenApi\Type;
use Sunrise\Http\Router\RouteInterface;
use Sunrise\Symfony\OpenApi\RouteAdapter;
use Sunrise\Symfony\OpenApi\RouteMetadata;
use Sunrise\Symfony\OpenApi\Tests\Fixture\ControllerFixture;
use Symfony\Component\Routing\Route;

trait TestKit
{
    private static function createOpenApiConfiguration(
        string $defaultTimestampFormat = OpenApiConfiguration::DEFAULT_TIMESTAMP_FORMAT,
    ): OpenApiConfiguration {
        return new OpenApiConfiguration(
            initialDocument: [],
            initialOperation: [],
            documentMediaType: MediaType::JSON,
            defaultTimestampFormat: $defaultTimestampFormat,
        );
    }

    private static function createControllerReflection(string $method): ReflectionMethod
    {
        return new ReflectionMethod(ControllerFixture::class, $method);
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

    private function mockRoute(string $path = '/foo'): RouteInterface
    {
        $route = $this->createStub(RouteInterface::class);
        $route->method('getPath')->willReturn($path);

        return $route;
    }

    /**
     * @param array<string, mixed> $defaults
     * @param array<string, string> $requirements
     * @param array<string, string|array{0: string, 1: string}> $mapping
     */
    private static function createRouteAdapter(
        string $name = 'foo',
        string $path = '/api/foo',
        mixed $controller = ControllerFixture::class,
        array $defaults = [],
        array $requirements = [],
        array $mapping = [],
        ?RouteMetadata $metadata = null,
    ): RouteAdapter {
        $defaults['_controller'] = $controller;
        $defaults['_route_mapping'] = $mapping;

        return new RouteAdapter(
            $name,
            new Route($path, $defaults, $requirements),
            $metadata ?? new RouteMetadata([], '', '', false, true),
        );
    }

    /**
     * @param array<array-key, mixed> $schema
     */
    private function mockPhpTypeSchemaResolverManager(
        array $schema = ['type' => 'schema'],
    ): OpenApiPhpTypeSchemaResolverManagerInterface {
        $manager = $this->createStub(OpenApiPhpTypeSchemaResolverManagerInterface::class);
        $manager
            ->method('resolvePhpTypeSchema')
            ->willReturnCallback(static function (Type $type) use ($schema): array {
                return $schema + ['phpType' => $type->name];
            });

        return $manager;
    }
}
