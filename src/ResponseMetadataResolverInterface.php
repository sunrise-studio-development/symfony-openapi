<?php

declare(strict_types=1);

namespace Sunrise\Symfony\OpenApi;

use ReflectionMethod;
use Symfony\Component\Routing\Route;

/**
 * @since 1.0.0
 */
interface ResponseMetadataResolverInterface
{
    public function resolveResponseMetadata(Route $route, ReflectionMethod $controller): ResponseMetadata;
}
