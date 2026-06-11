<?php

declare(strict_types=1);

namespace Sunrise\Symfony\OpenApi\Tests\OperationEnricher;

use PHPUnit\Framework\TestCase;
use Sunrise\Symfony\OpenApi\OperationEnricher\MapQueryStringOperationEnricher;
use Sunrise\Symfony\OpenApi\Tests\Fixture\DtoFixture;
use Sunrise\Symfony\OpenApi\Tests\TestKit;

final class MapQueryStringOperationEnricherTest extends TestCase
{
    use TestKit;

    public function testEnrichOperation(): void
    {
        $operation = [];

        $operationEnricher = new MapQueryStringOperationEnricher();
        $operationEnricher->setOpenApiPhpTypeSchemaResolverManager($this->createPhpTypeSchemaResolverManager());
        $operationEnricher->enrichOperation(
            $this->createRoute(),
            self::createControllerReflection('queryString'),
            $operation,
        );

        self::assertSame([
            [
                'in' => 'query',
                'name' => 'query',
                'schema' => ['type' => 'schema', 'phpType' => DtoFixture::class],
                'required' => true,
                'style' => 'form',
            ],
            [
                'in' => 'query',
                'name' => 'filter',
                'schema' => ['type' => 'schema', 'phpType' => DtoFixture::class],
                'required' => true,
                'style' => 'deepObject',
            ],
        ], $operation['parameters']);
    }
}
