<?php

declare(strict_types=1);

namespace Sunrise\Symfony\OpenApi\Tests\OperationEnricher;

use PHPUnit\Framework\TestCase;
use Sunrise\Http\Router\RouteInterface;
use Sunrise\Symfony\OpenApi\OperationEnricher\PathVariablesOperationEnricher;
use Sunrise\Symfony\OpenApi\Tests\TestKit;

final class PathVariablesOperationEnricherTest extends TestCase
{
    use TestKit;

    public function testEnrichOperation(): void
    {
        $operation = [];

        $operationEnricher = new PathVariablesOperationEnricher();
        $operationEnricher->setOpenApiPhpTypeSchemaResolverManager($this->mockPhpTypeSchemaResolverManager());
        $operationEnricher->enrichOperation(
            self::createRouteAdapter(path: '/api/pets/{petId}', requirements: ['petId' => '\d+']),
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

    public function testMappedVariable(): void
    {
        $operation = [];

        $operationEnricher = new PathVariablesOperationEnricher();
        $operationEnricher->setOpenApiPhpTypeSchemaResolverManager($this->mockPhpTypeSchemaResolverManager());
        $operationEnricher->enrichOperation(
            self::createRouteAdapter(path: '/api/pets/{id}', mapping: ['id' => 'petId']),
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

    public function testUnsupportedVariable(): void
    {
        $operation = [];

        $operationEnricher = new PathVariablesOperationEnricher();
        $operationEnricher->setOpenApiPhpTypeSchemaResolverManager($this->mockPhpTypeSchemaResolverManager());
        $operationEnricher->enrichOperation(
            self::createRouteAdapter(path: '/api/pets/{pet}'),
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

    public function testNonSymfonyRoute(): void
    {
        $operation = [];

        $operationEnricher = new PathVariablesOperationEnricher();
        $operationEnricher->setOpenApiPhpTypeSchemaResolverManager($this->mockPhpTypeSchemaResolverManager());
        $operationEnricher->enrichOperation(
            $this->createStub(RouteInterface::class),
            self::createControllerReflection('pathVariable'),
            $operation,
        );

        self::assertSame([], $operation);
    }
}
