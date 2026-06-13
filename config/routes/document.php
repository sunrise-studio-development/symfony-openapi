<?php

declare(strict_types=1);

use Sunrise\Symfony\OpenApi\Controller\DocumentController;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;

return static function (RoutingConfigurator $routes): void {
    $routes->add('document', DocumentController::ROUTE_PATH)
        ->controller(DocumentController::class)
        ->methods(['GET'])
        ->options(['api' => false]);
};
