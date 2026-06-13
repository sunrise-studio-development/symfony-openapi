<?php

declare(strict_types=1);

namespace Sunrise\Symfony\OpenApi\Tests\Command;

use PHPUnit\Framework\TestCase;
use Sunrise\Http\Router\OpenApi\OpenApiDocumentManagerInterface;
use Sunrise\Symfony\OpenApi\Command\BuildDocumentCommand;
use Sunrise\Symfony\OpenApi\RouteAdapter;
use Sunrise\Symfony\OpenApi\RouteMetadata;
use Sunrise\Symfony\OpenApi\RouteMetadataResolverInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Routing\RouterInterface;

final class OpenApiBuildDocumentCommandTest extends TestCase
{
    public function testExecute(): void
    {
        $apiRoute = new Route('/api/foo');
        $internalRoute = new Route('/internal/foo');

        $routeCollection = new RouteCollection();
        $routeCollection->add('api.foo', $apiRoute);
        $routeCollection->add('internal.foo', $internalRoute);

        $router = $this->createMock(RouterInterface::class);
        $router->expects(self::once())->method('getRouteCollection')->willReturn($routeCollection);

        $routeMetadataResolver = $this->createMock(RouteMetadataResolverInterface::class);
        $routeMetadataResolver
            ->expects(self::exactly(2))
            ->method('resolveRouteMetadata')
            ->willReturnCallback(static function (Route $route) use ($apiRoute): RouteMetadata {
                return new RouteMetadata([], '', '', false, $route === $apiRoute);
            });

        $openApiDocumentManager = $this->createMock(OpenApiDocumentManagerInterface::class);
        $openApiDocumentManager
            ->expects(self::once())
            ->method('buildDocument')
            ->with(self::callback(static function (array $routes): bool {
                $route = \reset($routes);

                return $route instanceof RouteAdapter
                    && $route->getName() === 'api.foo'
                    && \count($routes) === 1;
            }))
            ->willReturn(['foo' => 'bar']);
        $openApiDocumentManager
            ->expects(self::once())
            ->method('saveDocument')
            ->with(['foo' => 'bar']);

        $commandTester = new CommandTester(
            new BuildDocumentCommand(
                $router,
                $openApiDocumentManager,
                $routeMetadataResolver,
            )
        );

        self::assertSame(Command::SUCCESS, $commandTester->execute([]));
        self::assertSame("Done.\n", $commandTester->getDisplay());
    }
}
