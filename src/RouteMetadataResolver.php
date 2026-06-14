<?php

declare(strict_types=1);

namespace Sunrise\Symfony\OpenApi;

use Override;
use Symfony\Component\Routing\Route;

final class RouteMetadataResolver implements RouteMetadataResolverInterface
{
    #[Override]
    public function resolveRouteMetadata(Route $route): RouteMetadata
    {
        /** @var array<array-key, string>|string|null $tags */
        $tags = $route->getOption('tag') ?? $route->getOption('tags');

        /** @var string|null $summary */
        $summary = $route->getOption('summary');

        /** @var string|null $description */
        $description = $route->getOption('description');

        return new RouteMetadata(
            tags: (array) $tags,
            summary: (string) $summary,
            description: (string) $description,
            isDeprecated: self::isDeprecated($route),
            isApi: self::isApi($route),
        );
    }

    private static function isDeprecated(Route $route): bool
    {
        foreach (['deprecated', 'is_deprecated', 'isDeprecated'] as $option) {
            if ($route->hasOption($option)) {
                return (bool) $route->getOption($option);
            }
        }

        return false;
    }

    private static function isApi(Route $route): bool
    {
        foreach (['api', 'is_api', 'isApi'] as $option) {
            if ($route->hasOption($option)) {
                return (bool) $route->getOption($option);
            }
        }

        return \str_starts_with($route->getPath(), '/api/');
    }
}
