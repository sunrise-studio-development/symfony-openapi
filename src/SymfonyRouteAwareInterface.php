<?php

declare(strict_types=1);

namespace Sunrise\Symfony\OpenApi;

use Symfony\Component\Routing\Route;

/**
 * @since 1.0.0
 */
interface SymfonyRouteAwareInterface
{
    public function getSymfonyRoute(): Route;
}
