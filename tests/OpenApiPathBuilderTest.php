<?php

declare(strict_types=1);

namespace Sunrise\Symfony\OpenApi\Tests;

use PHPUnit\Framework\TestCase;
use Sunrise\Http\Router\RouteInterface;
use Sunrise\Symfony\OpenApi\OpenApiPathBuilder;

final class OpenApiPathBuilderTest extends TestCase
{
    public function testBuildPath(): void
    {
        $openApiPathBuilder = new OpenApiPathBuilder();

        $route = $this->createMock(RouteInterface::class);
        $route->method('getPath')->willReturn('/foo/{id}');

        self::assertSame('/foo/{id}', $openApiPathBuilder->buildPath($route));

        $route = $this->createMock(RouteInterface::class);
        $route->method('getPath')->willReturn('/foo/{!id}');

        self::assertSame('/foo/{id}', $openApiPathBuilder->buildPath($route));
    }
}
