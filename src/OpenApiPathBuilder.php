<?php

declare(strict_types=1);

namespace Sunrise\Symfony\OpenApi;

use Override;
use Sunrise\Http\Router\OpenApi\OpenApiPathBuilderInterface;
use Sunrise\Http\Router\RouteInterface;

final readonly class OpenApiPathBuilder implements OpenApiPathBuilderInterface
{
    /**
     * @see \Symfony\Component\Routing\Route::setPath
     */
    #[Override]
    public function buildPath(RouteInterface $route): string
    {
        return (string) \preg_replace('/\x7B\x21([^\x7B\x7D]+)\x7D/', '{$1}', $route->getPath());
    }
}
