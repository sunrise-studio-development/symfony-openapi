<?php

declare(strict_types=1);

namespace Sunrise\Symfony\OpenApi\Tests\OperationEnricher;

use PHPUnit\Framework\TestCase;
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
}
