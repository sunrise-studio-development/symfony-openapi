<?php

declare(strict_types=1);

namespace Sunrise\Symfony\OpenApi\Tests\OperationEnricher;

use PHPUnit\Framework\TestCase;
use ReflectionClass;
use stdClass;
use Sunrise\Symfony\OpenApi\OperationEnricher\MapRequestPayloadOperationEnricher;
use Sunrise\Symfony\OpenApi\Tests\Fixture\DtoFixture;
use Sunrise\Symfony\OpenApi\Tests\TestKit;

final class MapRequestPayloadOperationEnricherTest extends TestCase
{
    use TestKit;

    public function testEnrichOperation(): void
    {
        $operation = [];

        $operationEnricher = new MapRequestPayloadOperationEnricher();
        $operationEnricher->setOpenApiPhpTypeSchemaResolverManager($this->mockPhpTypeSchemaResolverManager());
        $operationEnricher->enrichOperation(
            $this->mockRoute(),
            self::createControllerReflection('requestPayload'),
            $operation,
        );

        self::assertSame([
            'content' => [
                'application/json' => [
                    'schema' => ['type' => 'schema', 'phpType' => DtoFixture::class],
                ],
            ],
            'required' => true,
        ], $operation['requestBody']);
    }

    public function testListPayload(): void
    {
        $operation = [];

        $operationEnricher = new MapRequestPayloadOperationEnricher();
        $operationEnricher->setOpenApiPhpTypeSchemaResolverManager($this->mockPhpTypeSchemaResolverManager());
        $operationEnricher->enrichOperation(
            $this->mockRoute(),
            self::createControllerReflection('requestPayloadList'),
            $operation,
        );

        self::assertSame([
            'content' => [
                'application/json' => [
                    'schema' => [
                        'type' => 'array',
                        'items' => ['type' => 'schema', 'phpType' => DtoFixture::class],
                    ],
                ],
            ],
            'required' => true,
        ], $operation['requestBody']);
    }

    public function testDefaultAcceptFormat(): void
    {
        $operation = [];

        $operationEnricher = new MapRequestPayloadOperationEnricher();
        $operationEnricher->setOpenApiPhpTypeSchemaResolverManager($this->mockPhpTypeSchemaResolverManager());
        $operationEnricher->enrichOperation(
            $this->mockRoute(),
            self::createControllerReflection('requestPayloadWithDefaultFormat'),
            $operation,
        );

        self::assertArrayHasKey('application/json', $operation['requestBody']['content']);
    }

    public function testNonMethodRequestHandler(): void
    {
        $operation = [];

        $operationEnricher = new MapRequestPayloadOperationEnricher();
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

        $operationEnricher = new MapRequestPayloadOperationEnricher();
        $operationEnricher->setOpenApiPhpTypeSchemaResolverManager($this->mockPhpTypeSchemaResolverManager());
        $operationEnricher->enrichOperation(
            $this->mockRoute(),
            self::createControllerReflection('requestPayloadWithUnmappedParameter'),
            $operation,
        );

        self::assertArrayHasKey('requestBody', $operation);
    }

    public function testWeight(): void
    {
        self::assertSame(10, new MapRequestPayloadOperationEnricher()->getWeight());
    }
}
