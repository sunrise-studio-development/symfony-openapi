<?php

declare(strict_types=1);

namespace Sunrise\Symfony\OpenApi\Tests\OperationEnricher;

use PHPUnit\Framework\TestCase;
use Sunrise\Http\Router\RouteInterface;
use Sunrise\Symfony\OpenApi\OperationEnricher\PathVariablesOperationEnricher;
use Sunrise\Symfony\OpenApi\RouteAdapter;
use Sunrise\Symfony\OpenApi\RouteMetadata;
use Sunrise\Symfony\OpenApi\Tests\TestKit;
use Symfony\Component\Routing\Route;

final class PathVariablesOperationEnricherTest extends TestCase
{
    use TestKit;

    public function testEnrichOperation(): void
    {
        $operation = [];

        $operationEnricher = new PathVariablesOperationEnricher();
        $operationEnricher->setOpenApiPhpTypeSchemaResolverManager($this->createPhpTypeSchemaResolverManager());
        $operationEnricher->enrichOperation(
            self::createRouteAdapter(new Route('/api/pets/{petId}', requirements: ['petId' => '\d+'])),
            self::createControllerReflection('pathVariable'),
            $operation,
        );

        self::assertSame([
            [
                'in' => 'path',
                'name' => 'petId',
                'schema' => [
                    'type' => 'schema',
                    'phpType' => 'int',
                    'pattern' => '^\d+$',
                ],
                'required' => true,
            ],
        ], $operation['parameters']);
    }

    public function testEnrichOperationWithMappedVariable(): void
    {
        $operation = [];

        $operationEnricher = new PathVariablesOperationEnricher();
        $operationEnricher->setOpenApiPhpTypeSchemaResolverManager($this->createPhpTypeSchemaResolverManager());
        $operationEnricher->enrichOperation(
            new RouteAdapter(
                'foo',
                new Route('/api/pets/{id}', ['_route_mapping' => ['id' => 'petId']]),
                new RouteMetadata([], '', '', false, true),
            ),
            self::createControllerReflection('pathVariable'),
            $operation,
        );

        self::assertSame([
            [
                'in' => 'path',
                'name' => 'id',
                'schema' => [
                    'type' => 'schema',
                    'phpType' => 'int',
                ],
                'required' => true,
            ],
        ], $operation['parameters']);
    }

    public function testSkipsUnsupportedVariable(): void
    {
        $operation = [];

        $operationEnricher = new PathVariablesOperationEnricher();
        $operationEnricher->setOpenApiPhpTypeSchemaResolverManager($this->createPhpTypeSchemaResolverManager());
        $operationEnricher->enrichOperation(
            self::createRouteAdapter(new Route('/api/pets/{pet}')),
            self::createControllerReflection('entityPathVariable'),
            $operation,
        );

        self::assertSame([
            [
                'in' => 'path',
                'name' => 'pet',
                'schema' => [
                    'type' => 'string',
                ],
                'required' => true,
            ],
        ], $operation['parameters']);
    }

    public function testSkipsNonSymfonyRoute(): void
    {
        $operation = [];

        $operationEnricher = new PathVariablesOperationEnricher();
        $operationEnricher->setOpenApiPhpTypeSchemaResolverManager($this->createPhpTypeSchemaResolverManager());
        $operationEnricher->enrichOperation(
            $this->createMock(RouteInterface::class),
            self::createControllerReflection('pathVariable'),
            $operation,
        );

        self::assertSame([], $operation);
    }
}
