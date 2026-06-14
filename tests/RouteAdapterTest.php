<?php

declare(strict_types=1);

namespace Sunrise\Symfony\OpenApi\Tests;

use PHPUnit\Framework\TestCase;
use Sunrise\Symfony\OpenApi\RouteAdapter;
use Sunrise\Symfony\OpenApi\RouteMetadata;
use Symfony\Component\Routing\Route;

final class RouteAdapterTest extends TestCase
{
    public function testRouteData(): void
    {
        $route = $this->createRoute();
        $adapter = new RouteAdapter('pets.show', $route, new RouteMetadata([], '', '', false, true));

        self::assertSame('pets.show', $adapter->getName());
        self::assertSame('/api/pets/{id}', $adapter->getPath());
        self::assertSame('controller', $adapter->getRequestHandler());
        self::assertSame(['id' => '\d+'], $adapter->getPatterns());
        self::assertSame(['GET'], $adapter->getMethods());
        self::assertSame($route, $adapter->getSymfonyRoute());
    }

    public function testAttributes(): void
    {
        $adapter = new RouteAdapter('pets.show', $this->createRoute(), new RouteMetadata([], '', '', false, true));

        self::assertSame(['_controller' => 'controller', 'foo' => 'bar'], $adapter->getAttributes());
        self::assertTrue($adapter->hasAttribute('foo'));
        self::assertFalse($adapter->hasAttribute('bar'));
        self::assertSame('bar', $adapter->getAttribute('foo'));
        self::assertSame('baz', $adapter->getAttribute('bar', 'baz'));
    }

    public function testEmptySunriseRouteData(): void
    {
        $adapter = new RouteAdapter('pets.show', $this->createRoute(), new RouteMetadata([], '', '', false, true));

        self::assertSame([], $adapter->getMiddlewares());
        self::assertSame([], $adapter->getConsumedMediaTypes());
        self::assertSame([], $adapter->getProducedMediaTypes());
        self::assertNull($adapter->getPattern());
    }

    public function testMetadata(): void
    {
        $metadata = new RouteMetadata(['Pets'], 'Summary', 'Description', true, true);
        $adapter = new RouteAdapter('pets.show', $this->createRoute(), $metadata);

        self::assertSame(['Pets'], $adapter->getTags());
        self::assertSame('Summary', $adapter->getSummary());
        self::assertSame('Description', $adapter->getDescription());
        self::assertTrue($adapter->isDeprecated());
        self::assertTrue($adapter->isApiRoute());
    }

    public function testWithAddedAttributes(): void
    {
        $adapter = new RouteAdapter(
            'foo',
            new Route('/api/foo', ['foo' => 'bar']),
            new RouteMetadata(['tag'], 'summary', 'description', false, true),
        );

        $clone = $adapter->withAddedAttributes(['bar' => 'baz']);

        self::assertNotSame($adapter, $clone);
        self::assertSame(['foo' => 'bar'], $adapter->getAttributes());
        self::assertSame(['foo' => 'bar', 'bar' => 'baz'], $clone->getAttributes());
    }

    private function createRoute(): Route
    {
        return new Route(
            '/api/pets/{id}',
            defaults: [
                '_controller' => 'controller',
                'foo' => 'bar',
            ],
            requirements: [
                'id' => '\d+',
            ],
            methods: ['GET'],
        );
    }
}
