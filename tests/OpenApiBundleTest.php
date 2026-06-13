<?php

declare(strict_types=1);

namespace Sunrise\Symfony\OpenApi\Tests;

use PHPUnit\Framework\TestCase;
use Sunrise\Http\Router\OpenApi\OpenApiConfiguration;
use Sunrise\Symfony\OpenApi\OpenApiBundle;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;

final class OpenApiBundleTest extends TestCase
{
    public function testLoadExtension(): void
    {
        $container = new ContainerBuilder();
        $loader = new PhpFileLoader($container, new FileLocator(__DIR__ . '/../config'));
        $instanceof = [];
        $configurator = new ContainerConfigurator(
            $container,
            $loader,
            $instanceof,
            __DIR__ . '/../config/services.php',
            __DIR__ . '/../config/services.php',
        );

        new OpenApiBundle()->loadExtension([], $configurator, $container);

        self::assertTrue($container->hasDefinition(OpenApiConfiguration::class));
        self::assertSame('/openapi', $container->getParameter('openapi.document_uri'));
    }
}
