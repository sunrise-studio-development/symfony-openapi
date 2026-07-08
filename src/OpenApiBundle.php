<?php

declare(strict_types=1);

namespace Sunrise\Symfony\OpenApi;

use Override;
use Sunrise\Http\Router\OpenApi\OpenApiOperationEnricherInterface;
use Sunrise\Http\Router\OpenApi\OpenApiPhpTypeSchemaResolverInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;

/**
 * @psalm-suppress DeprecatedInterface
 */
final class OpenApiBundle extends AbstractBundle
{
    #[Override]
    public function build(ContainerBuilder $container): void
    {
        $container->registerForAutoconfiguration(OpenApiOperationEnricherInterface::class)
            ->addTag('openapi.operation_enricher');

        $container->registerForAutoconfiguration(OpenApiPhpTypeSchemaResolverInterface::class)
            ->addTag('openapi.php_type_schema_resolver');
    }

    /**
     * @inheritDoc
     *
     * @param array<array-key, mixed> $config
     */
    #[Override]
    public function loadExtension(array $config, ContainerConfigurator $configurator, ContainerBuilder $container): void
    {
        $configurator->import(__DIR__ . '/../config/services.php');
    }
}
