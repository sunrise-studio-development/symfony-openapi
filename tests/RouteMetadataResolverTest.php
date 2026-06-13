<?php

declare(strict_types=1);

namespace Sunrise\Symfony\OpenApi\Tests;

use Generator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Sunrise\Symfony\OpenApi\RouteMetadataResolver;
use Symfony\Component\Routing\Route;

final class RouteMetadataResolverTest extends TestCase
{
    public function testResolveRouteMetadata(): void
    {
        $route = new Route('/internal/foo', options: [
            'tags' => 'Pets',
            'summary' => 'Summary',
            'description' => 'Description',
            'deprecated' => true,
            'api' => true,
        ]);

        $metadata = new RouteMetadataResolver()->resolveRouteMetadata($route);

        self::assertSame(['Pets'], $metadata->tags);
        self::assertSame('Summary', $metadata->summary);
        self::assertSame('Description', $metadata->description);
        self::assertTrue($metadata->isDeprecated);
        self::assertTrue($metadata->isApi);
    }

    public function testResolveTagAlias(): void
    {
        $metadata = new RouteMetadataResolver()->resolveRouteMetadata(
            new Route('/api/foo', options: [
                'tag' => ['Pets'],
            ])
        );

        self::assertSame(['Pets'], $metadata->tags);
    }

    #[DataProvider('deprecatedOptionProvider')]
    public function testResolveDeprecatedOption(string $option): void
    {
        $metadata = new RouteMetadataResolver()->resolveRouteMetadata(
            new Route('/api/foo', options: [
                $option => true,
            ])
        );

        self::assertTrue($metadata->isDeprecated);
    }

    /**
     * @return Generator<array-key, array<array-key, string>>
     */
    public static function deprecatedOptionProvider(): Generator
    {
        yield ['deprecated'];
        yield ['is_deprecated'];
        yield ['isDeprecated'];
    }

    #[DataProvider('apiOptionProvider')]
    public function testResolveApiOption(string $option): void
    {
        $metadata = new RouteMetadataResolver()->resolveRouteMetadata(
            new Route('/internal/foo', options: [
                $option => true,
            ])
        );

        self::assertTrue($metadata->isApi);
    }

    /**
     * @return Generator<array-key, array<array-key, string>>
     */
    public static function apiOptionProvider(): Generator
    {
        yield ['api'];
        yield ['is_api'];
        yield ['isApi'];
    }

    public function testApiRoutePath(): void
    {
        $metadata = new RouteMetadataResolver()->resolveRouteMetadata(new Route('/api/foo'));

        self::assertTrue($metadata->isApi);
    }

    public function testInternalRoutePath(): void
    {
        $metadata = new RouteMetadataResolver()->resolveRouteMetadata(new Route('/internal/foo'));

        self::assertFalse($metadata->isApi);
    }
}
