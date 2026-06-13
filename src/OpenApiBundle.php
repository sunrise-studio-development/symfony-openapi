<?php

declare(strict_types=1);

namespace Sunrise\Symfony\OpenApi;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;

/**
 * @psalm-suppress DeprecatedInterface
 */
final class OpenApiBundle extends AbstractBundle
{
    /**
     * @inheritDoc
     *
     * @param array<array-key, mixed> $config
     */
    public function loadExtension(array $config, ContainerConfigurator $configurator, ContainerBuilder $container): void
    {
        $configurator->import(__DIR__ . '/../config/services.php');
    }
}
