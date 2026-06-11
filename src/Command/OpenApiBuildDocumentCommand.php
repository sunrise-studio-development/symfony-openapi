<?php

declare(strict_types=1);

namespace Sunrise\Symfony\OpenApi\Command;

use Sunrise\Http\Router\OpenApi\OpenApiDocumentManagerInterface;
use Sunrise\Symfony\OpenApi\RouteAdapter;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Routing\RouterInterface;

/**
 * @since 1.0.0
 */
#[AsCommand('openapi:build-document', 'Builds the OpenAPI document.')]
final class OpenApiBuildDocumentCommand extends Command
{
    public function __construct(
        private readonly RouterInterface $router,
        private readonly OpenApiDocumentManagerInterface $openApiDocumentManager,
    ) {
        parent::__construct();
    }

    /**
     * @inheritDoc
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $apiRoutes = [];
        foreach ($this->router->getRouteCollection() as $routeName => $route) {
            $routeAdapter = new RouteAdapter($routeName, $route);
            if ($routeAdapter->isApiRoute()) {
                $apiRoutes[] = $routeAdapter;
            }
        }

        $this->openApiDocumentManager->saveDocument(
            $this->openApiDocumentManager->buildDocument($apiRoutes)
        );

        $output->writeln('Done.');

        return self::SUCCESS;
    }
}
