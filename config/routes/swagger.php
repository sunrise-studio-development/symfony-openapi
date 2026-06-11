<?php

declare(strict_types=1);

use Sunrise\Symfony\OpenApi\Controller\SwaggerController;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;

return static function (RoutingConfigurator $routes): void {
    $routes->add('swagger', SwaggerController::ROUTE_PATH)
        ->controller(SwaggerController::class)
        ->methods(['GET'])
        ->options(['api' => false]);
};
