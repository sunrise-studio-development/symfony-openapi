<?php

declare(strict_types=1);

namespace Sunrise\Symfony\OpenApi;

use ReflectionMethod;
use Symfony\Component\Routing\Route;

interface ResponseMetadataResolverInterface
{
    public function resolveResponseMetadata(Route $route, ReflectionMethod $controller): ResponseMetadata;
}
