<?php

declare(strict_types=1);

namespace Sunrise\Symfony\OpenApi\Controller;

use Sunrise\Http\Router\Helper\TemplateRenderer;
use Sunrise\Http\Router\OpenApi\Controller\SwaggerController as SunriseSwaggerController;
use Sunrise\Http\Router\OpenApi\SwaggerConfiguration;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

final class SwaggerController
{
    public const ROUTE_PATH = SunriseSwaggerController::ROUTE_PATH;

    public function __construct(
        private readonly SwaggerConfiguration $swaggerConfiguration,
    ) {
    }

    /**
     * @throws Throwable
     */
    public function __invoke(): Response
    {
        $content = TemplateRenderer::renderTemplate(
            filename: $this->swaggerConfiguration->templateFilename,
            variables: [
                ...$this->swaggerConfiguration->templateVariables,
                SunriseSwaggerController::CSS_URLS_VAR_NAME => $this->swaggerConfiguration->cssUrls,
                SunriseSwaggerController::JS_URLS_VAR_NAME => $this->swaggerConfiguration->jsUrls,
                SunriseSwaggerController::OPENAPI_URI_VAR_NAME => $this->swaggerConfiguration->openapiUri,
            ],
        );

        return new Response($content, Response::HTTP_OK, [
            'Content-Type' => 'text/html; charset=UTF-8',
        ]);
    }
}
