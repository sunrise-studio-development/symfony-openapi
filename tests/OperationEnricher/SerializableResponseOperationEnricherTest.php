<?php

declare(strict_types=1);

namespace Sunrise\Symfony\OpenApi\Tests\OperationEnricher;

use PHPUnit\Framework\TestCase;
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
}
