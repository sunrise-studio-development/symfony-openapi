<?php

declare(strict_types=1);

namespace Sunrise\Symfony\OpenApi\Tests\Controller;

use PHPUnit\Framework\TestCase;
use Sunrise\Http\Router\OpenApi\OpenApiDocumentManagerInterface;
use Sunrise\Symfony\OpenApi\Controller\OpenApiController;
use Sunrise\Symfony\OpenApi\Tests\TestKit;

final class OpenApiControllerTest extends TestCase
{
    use TestKit;

    public function testInvoke(): void
    {
        $document = \fopen('php://memory', 'rb+');
        self::assertIsResource($document);

        \fwrite($document, '{"openapi":"3.1.1"}');
        \rewind($document);

        $openApiDocumentManager = $this->createMock(OpenApiDocumentManagerInterface::class);
        $openApiDocumentManager->method('openDocument')->willReturn($document);

        $response = (new OpenApiController(self::createOpenApiConfiguration(), $openApiDocumentManager))();

        self::assertSame(200, $response->getStatusCode());
        self::assertSame('application/json; charset=UTF-8', $response->headers->get('Content-Type'));
        self::assertSame('{"openapi":"3.1.1"}', $response->getContent());
    }
}
