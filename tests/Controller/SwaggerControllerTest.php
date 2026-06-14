<?php

declare(strict_types=1);

namespace Sunrise\Symfony\OpenApi\Tests\Controller;

use PHPUnit\Framework\TestCase;
use Sunrise\Http\Router\OpenApi\SwaggerConfiguration;
use Sunrise\Symfony\OpenApi\Controller\SwaggerController;

final class SwaggerControllerTest extends TestCase
{
    public function testInvoke(): void
    {
        $response = (new SwaggerController(new SwaggerConfiguration()))();

        self::assertSame(200, $response->getStatusCode());
        self::assertSame('text/html; charset=UTF-8', $response->headers->get('Content-Type'));
    }

    public function testOpenApiUri(): void
    {
        $response = (new SwaggerController(new SwaggerConfiguration(openapiUri: '/openapi.json')))();

        self::assertStringContainsString('/openapi.json', (string) $response->getContent());
    }
}
