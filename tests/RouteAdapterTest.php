<?php

declare(strict_types=1);

namespace Sunrise\Symfony\OpenApi\Tests;

use PHPUnit\Framework\TestCase;
use Sunrise\Symfony\OpenApi\RouteAdapter;
use Sunrise\Symfony\OpenApi\RouteMetadata;
use Symfony\Component\Routing\Route;

final class RouteAdapterTest extends TestCase
{
    public function testConstructor(): void
    {
        $route = new Route(
            '/api/pets/{id}',
            defaults: [
                '_controller' => 'controller',
                'foo' => 'bar',
            ],
            requirements: [
                'id' => '\d+',
            ],
            options: [
                'option' => 'value',
            ],
            methods: ['GET'],
        );

        $metadata = new RouteMetadata(['Pets'], 'Summary', 'Description', true, true);
        $adapter = new RouteAdapter('pets.show', $route, $metadata);

        self::assertSame('pets.show', $adapter->getName());
        self::assertSame('/api/pets/{id}', $adapter->getPath());
        self::assertSame('controller', $adapter->getRequestHandler());
        self::assertSame(['id' => '\d+'], $adapter->getPatterns());
        self::assertSame(['GET'], $adapter->getMethods());
        self::assertSame(['_controller' => 'controller', 'foo' => 'bar'], $adapter->getAttributes());
        self::assertTrue($adapter->hasAttribute('foo'));
        self::assertFalse($adapter->hasAttribute('bar'));
        self::assertSame('bar', $adapter->getAttribute('foo'));
        self::assertSame('baz', $adapter->getAttribute('bar', 'baz'));
        self::assertSame([], $adapter->getMiddlewares());
        self::assertSame([], $adapter->getConsumedMediaTypes());
        self::assertSame([], $adapter->getProducedMediaTypes());
        self::assertSame(['Pets'], $adapter->getTags());
        self::assertSame('Summary', $adapter->getSummary());
        self::assertSame('Description', $adapter->getDescription());
        self::assertTrue($adapter->isDeprecated());
        self::assertTrue($adapter->isApiRoute());
        self::assertNull($adapter->getPattern());
        self::assertSame($route, $adapter->getSymfonyRoute());
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
}
