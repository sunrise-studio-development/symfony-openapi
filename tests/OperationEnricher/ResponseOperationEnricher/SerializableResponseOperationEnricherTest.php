<?php

declare(strict_types=1);

namespace Sunrise\Symfony\OpenApi\Tests\OperationEnricher\ResponseOperationEnricher;

use PHPUnit\Framework\TestCase;
use Sunrise\Symfony\OpenApi\OperationEnricher\ResponseOperationEnricher\SerializableResponseOperationEnricher;
use Sunrise\Symfony\OpenApi\ResponseMetadata;
use Sunrise\Symfony\OpenApi\ResponseMetadataResolverInterface;
use Sunrise\Symfony\OpenApi\Tests\Fixture\DtoFixture;
use Sunrise\Symfony\OpenApi\Tests\TestKit;

final class SerializableResponseOperationEnricherTest extends TestCase
{
    use TestKit;

    public function testEnrichOperation(): void
    {
        $operation = [];

        $responseMetadataResolver = $this->createMock(ResponseMetadataResolverInterface::class);
        $responseMetadataResolver->method('resolveResponseMetadata')->willReturn(new ResponseMetadata(201, ['json']));

        $operationEnricher = new SerializableResponseOperationEnricher($responseMetadataResolver, 200, ['xml']);
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

    public function testDefaults(): void
    {
        $operation = [];

        $responseMetadataResolver = $this->createMock(ResponseMetadataResolverInterface::class);
        $responseMetadataResolver->method('resolveResponseMetadata')->willReturn(new ResponseMetadata(null, []));

        $operationEnricher = new SerializableResponseOperationEnricher($responseMetadataResolver, 200, ['json']);
        $operationEnricher->setOpenApiConfiguration(self::createOpenApiConfiguration());
        $operationEnricher->setOpenApiPhpTypeSchemaResolverManager($this->mockPhpTypeSchemaResolverManager());
        $operationEnricher->enrichOperation(
            self::createRouteAdapter(),
            self::createControllerReflection('serializableResponse'),
            $operation,
        );

        self::assertSame([
            200 => [
                'description' => 'The operation was successful.',
                'content' => [
                    'application/json' => [
                        'schema' => ['type' => 'schema', 'phpType' => DtoFixture::class],
                    ],
                ],
            ],
        ], $operation['responses']);
    }

    public function testSymfonyResponse(): void
    {
        $operation = [];

        $operationEnricher = new SerializableResponseOperationEnricher(
            $this->createMock(ResponseMetadataResolverInterface::class),
        );
        $operationEnricher->setOpenApiConfiguration(self::createOpenApiConfiguration());
        $operationEnricher->setOpenApiPhpTypeSchemaResolverManager($this->mockPhpTypeSchemaResolverManager());
        $operationEnricher->enrichOperation(
            self::createRouteAdapter(),
            self::createControllerReflection('symfonyResponse'),
            $operation,
        );

        self::assertSame([], $operation);
    }
}
