<?php

declare(strict_types=1);

namespace Sunrise\Symfony\OpenApi;

use ReflectionMethod;
use Symfony\Component\Routing\Route;

/**
 * @since 1.0.0
 */
final readonly class ResponseMetadataResolver implements ResponseMetadataResolverInterface
{
    public function resolveResponseMetadata(Route $route, ReflectionMethod $controller): ResponseMetadata
    {
        /** @var int|null $status */
        $status = $route->getOption('response_status');

        /** @var array<array-key, string> $formats */
        $formats = (array) $route->getOption('response_formats');

        return new ResponseMetadata($status, $formats);
    }
}
