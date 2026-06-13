<?php

declare(strict_types=1);

namespace Sunrise\Symfony\OpenApi\Controller;

use RuntimeException;
use Sunrise\Http\Router\OpenApi\Controller\OpenApiController as SunriseOpenApiController;
use Sunrise\Http\Router\OpenApi\OpenApiConfiguration;
use Sunrise\Http\Router\OpenApi\OpenApiDocumentManagerInterface;
use Symfony\Component\HttpFoundation\Response;

final readonly class DocumentController
{
    public const ROUTE_PATH = SunriseOpenApiController::ROUTE_PATH;

    public function __construct(
        private OpenApiConfiguration $openApiConfiguration,
        private OpenApiDocumentManagerInterface $documentManager,
    ) {
    }

    public function __invoke(): Response
    {
        $document = $this->documentManager->openDocument();

        try {
            $content = \stream_get_contents($document);
        } finally {
            \fclose($document);
        }

        if ($content === false) {
            throw new RuntimeException('The OpenAPI document could not be read.');
        }

        $contentType = $this->openApiConfiguration->documentMediaType->getIdentifier();
        $contentType .= '; charset=UTF-8'; // OpenAPI documents are textual payloads...

        return new Response($content, Response::HTTP_OK, [
            'Content-Type' => $contentType,
        ]);
    }
}
