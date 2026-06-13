<?php

declare(strict_types=1);

namespace Sunrise\Symfony\OpenApi\Tests\OperationEnricher;

use PHPUnit\Framework\TestCase;
use ReflectionClass;
use stdClass;
use Sunrise\Symfony\OpenApi\OperationEnricher\SerializableResponseOperationEnricher;
use Sunrise\Symfony\OpenApi\Tests\Fixture\DtoFixture;
use Sunrise\Symfony\OpenApi\Tests\TestKit;

final class SerializableResponseOperationEnricherTest extends TestCase
{
    use TestKit;

    public function testEnrichOperation(): void
    {
        $operation = [];

        $operationEnricher = new SerializableResponseOperationEnricher();
        $operationEnricher->setOpenApiConfiguration(self::createOpenApiConfiguration());
        $operationEnricher->setOpenApiPhpTypeSchemaResolverManager($this->mockPhpTypeSchemaResolverManager());
        $operationEnricher->enrichOperation(
            self::createRouteAdapter(),
            self::createControllerReflection('serializableResponse'),
            $operation,
        );

        self::assertSame([
            201 => [
                'description' => 'The operation was successful.',
                'content' => [
                    'application/json' => [
                        'schema' => ['type' => 'schema', 'phpType' => DtoFixture::class],
                    ],
                ],
            ],
        ], $operation['responses']);
    }

    public function testResponseFormat(): void
    {
        $operation = [];

        $operationEnricher = new SerializableResponseOperationEnricher();
        $operationEnricher->setOpenApiConfiguration(self::createOpenApiConfiguration());
        $operationEnricher->setOpenApiPhpTypeSchemaResolverManager($this->mockPhpTypeSchemaResolverManager());
        $operationEnricher->enrichOperation(
            self::createRouteAdapter(defaults: ['_format' => 'xml']),
            self::createControllerReflection('serializableResponse'),
            $operation,
        );

        self::assertArrayHasKey('text/xml', $operation['responses'][201]['content']);
    }

    public function testUnknownResponseFormat(): void
    {
        $operation = [];

        $operationEnricher = new SerializableResponseOperationEnricher();
        $operationEnricher->setOpenApiConfiguration(self::createOpenApiConfiguration());
        $operationEnricher->setOpenApiPhpTypeSchemaResolverManager($this->mockPhpTypeSchemaResolverManager());
        $operationEnricher->enrichOperation(
            self::createRouteAdapter(defaults: ['_format' => 'unknown']),
            self::createControllerReflection('serializableResponse'),
            $operation,
        );

        self::assertArrayHasKey('application/json', $operation['responses'][201]['content']);
    }

    public function testSymfonyResponse(): void
    {
        $operation = [];

        $operationEnricher = new SerializableResponseOperationEnricher();
        $operationEnricher->setOpenApiConfiguration(self::createOpenApiConfiguration());
        $operationEnricher->setOpenApiPhpTypeSchemaResolverManager($this->mockPhpTypeSchemaResolverManager());
        $operationEnricher->enrichOperation(
            self::createRouteAdapter(),
            self::createControllerReflection('symfonyResponse'),
            $operation,
        );

        self::assertSame([], $operation);
    }

    public function testNonMethodRequestHandler(): void
    {
        $operation = [];

        $operationEnricher = new SerializableResponseOperationEnricher();
        $operationEnricher->setOpenApiConfiguration(self::createOpenApiConfiguration());
        $operationEnricher->setOpenApiPhpTypeSchemaResolverManager($this->mockPhpTypeSchemaResolverManager());
        $operationEnricher->enrichOperation(
            self::createRouteAdapter(),
            new ReflectionClass(new stdClass()),
            $operation,
        );

        self::assertSame([], $operation);
    }

    public function testWeight(): void
    {
        self::assertSame(20, new SerializableResponseOperationEnricher()->getWeight());
    }
}
