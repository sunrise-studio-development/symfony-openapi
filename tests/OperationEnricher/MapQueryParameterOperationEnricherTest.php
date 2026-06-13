<?php

declare(strict_types=1);

namespace Sunrise\Symfony\OpenApi\Tests\OperationEnricher;

use PHPUnit\Framework\TestCase;
use ReflectionClass;
use stdClass;
use Sunrise\Symfony\OpenApi\OperationEnricher\MapQueryParameterOperationEnricher;
use Sunrise\Symfony\OpenApi\Tests\TestKit;

final class MapQueryParameterOperationEnricherTest extends TestCase
{
    use TestKit;

    public function testEnrichOperation(): void
    {
        $operation = [];

        $operationEnricher = new MapQueryParameterOperationEnricher();
        $operationEnricher->setOpenApiPhpTypeSchemaResolverManager($this->mockPhpTypeSchemaResolverManager());
        $operationEnricher->enrichOperation(
            $this->mockRoute(),
            self::createControllerReflection('queryParameter'),
            $operation,
        );

        self::assertSame([
            [
                'in' => 'query',
                'name' => 'foo',
                'schema' => ['type' => 'schema', 'phpType' => 'int'],
                'required' => true,
            ],
            [
                'in' => 'query',
                'name' => 'baz',
                'schema' => ['type' => 'schema', 'phpType' => 'string'],
                'required' => false,
            ],
            [
                'in' => 'query',
                'name' => 'qux',
                'schema' => [
                    'type' => 'array',
                    'items' => ['type' => 'schema', 'phpType' => 'string'],
                ],
                'required' => false,
            ],
        ], $operation['parameters']);
    }

    public function testNonMethodRequestHandler(): void
    {
        $operation = [];

        $operationEnricher = new MapQueryParameterOperationEnricher();
        $operationEnricher->setOpenApiPhpTypeSchemaResolverManager($this->mockPhpTypeSchemaResolverManager());
        $operationEnricher->enrichOperation(
            $this->mockRoute(),
            new ReflectionClass(new stdClass()),
            $operation,
        );

        self::assertSame([], $operation);
    }

    public function testUnmappedParameter(): void
    {
        $operation = [];

        $operationEnricher = new MapQueryParameterOperationEnricher();
        $operationEnricher->setOpenApiPhpTypeSchemaResolverManager($this->mockPhpTypeSchemaResolverManager());
        $operationEnricher->enrichOperation(
            $this->mockRoute(),
            self::createControllerReflection('queryParameterWithUnmappedParameter'),
            $operation,
        );

        self::assertSame('bar', $operation['parameters'][0]['name']);
    }

    public function testWeight(): void
    {
        self::assertSame(30, new MapQueryParameterOperationEnricher()->getWeight());
    }
}
