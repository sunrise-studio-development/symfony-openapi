<?php

declare(strict_types=1);

use Sunrise\Symfony\OpenApi\Controller\OpenApiController;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;

return static function (RoutingConfigurator $routes): void {
    $routes->add('openapi', OpenApiController::ROUTE_PATH)
        ->controller(OpenApiController::class)
        ->methods(['GET'])
        ->options(['api' => false]);
};
