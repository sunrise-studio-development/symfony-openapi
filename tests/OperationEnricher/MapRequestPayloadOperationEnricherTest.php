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
        $operationEnricher->setOpenApiPhpTypeSchemaResolverManager($this->createPhpTypeSchemaResolverManager());
        $operationEnricher->enrichOperation(
            $this->createRoute(),
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

    public function testEnrichListPayloadOperation(): void
    {
        $operation = [];

        $operationEnricher = new MapRequestPayloadOperationEnricher();
        $operationEnricher->setOpenApiPhpTypeSchemaResolverManager($this->createPhpTypeSchemaResolverManager());
        $operationEnricher->enrichOperation(
            $this->createRoute(),
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
