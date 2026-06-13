<?php

declare(strict_types=1);

namespace Sunrise\Symfony\OpenApi;

use Symfony\Component\Routing\Route;

interface SymfonyRouteAwareInterface
{
    public function getSymfonyRoute(): Route;
}
