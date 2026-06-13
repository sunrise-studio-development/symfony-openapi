<?php

declare(strict_types=1);

namespace Sunrise\Symfony\OpenApi\Command;

use Sunrise\Http\Router\OpenApi\OpenApiDocumentManagerInterface;
use Sunrise\Symfony\OpenApi\RouteAdapter;
use Sunrise\Symfony\OpenApi\RouteMetadataResolverInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Routing\RouterInterface;

#[AsCommand('openapi:build-document', 'Builds the OpenAPI document.')]
final class OpenApiBuildDocumentCommand extends Command
{
    public function __construct(
        private readonly RouterInterface $router,
        private readonly OpenApiDocumentManagerInterface $documentManager,
        private readonly RouteMetadataResolverInterface $routeMetadataResolver,
    ) {
        parent::__construct();
    }

    /**
     * @inheritDoc
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $apiRoutes = [];
        foreach ($this->router->getRouteCollection() as $name => $route) {
            $metadata = $this->routeMetadataResolver->resolveRouteMetadata($route);
            if ($metadata->isApi) {
                $apiRoutes[] = new RouteAdapter($name, $route, $metadata);
            }
        }

        $this->documentManager->saveDocument(
            $this->documentManager->buildDocument($apiRoutes)
        );

        $output->writeln('Done.');

        return self::SUCCESS;
    }
}
