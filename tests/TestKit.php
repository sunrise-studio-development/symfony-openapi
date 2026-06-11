<?php

declare(strict_types=1);

namespace Sunrise\Symfony\OpenApi\Tests;

use ReflectionMethod;
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

    private function createRoute(string $path = '/foo'): RouteInterface
    {
        $route = $this->createMock(RouteInterface::class);
        $route->method('getPath')->willReturn($path);

        return $route;
    }

    private static function createRouteAdapter(?Route $route = null): RouteAdapter
    {
        return new RouteAdapter(
            'foo',
            $route ?? new Route('/api/foo', ['_controller' => ControllerFixture::class]),
            new RouteMetadata([], '', '', false, true),
        );
    }

    /**
     * @param array<array-key, mixed> $schema
     */
    private function createPhpTypeSchemaResolverManager(
        array $schema = ['type' => 'schema'],
    ): OpenApiPhpTypeSchemaResolverManagerInterface {
        $manager = $this->createMock(OpenApiPhpTypeSchemaResolverManagerInterface::class);
        $manager
            ->method('resolvePhpTypeSchema')
            ->willReturnCallback(static function (Type $type) use ($schema): array {
                return $schema + ['phpType' => $type->name];
            });

        return $manager;
    }
}
