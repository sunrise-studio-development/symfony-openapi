<?php

declare(strict_types=1);

namespace Sunrise\Symfony\OpenApi;

use Override;
use ReflectionMethod;
use Symfony\Component\Routing\Route;

final readonly class ResponseMetadataResolver implements ResponseMetadataResolverInterface
{
    #[Override]
    public function resolveResponseMetadata(Route $route, ReflectionMethod $controller): ResponseMetadata
    {
        /** @var int|null $code */
        $code = $route->getOption('response_code');

        /** @var string|array<array-key, string>|null $formats */
        $formats = $route->getOption('response_format')
            ?? $route->getOption('response_formats');

        return new ResponseMetadata($code, (array) $formats);
    }
}
