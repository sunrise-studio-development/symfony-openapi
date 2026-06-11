<?php

declare(strict_types=1);

namespace Sunrise\Symfony\OpenApi\Tests\OperationEnricher\ResponseOperationEnricher;

use PHPUnit\Framework\TestCase;
use Sunrise\Symfony\OpenApi\OperationEnricher\ResponseOperationEnricher\EmptyResponseOperationEnricher;
use Sunrise\Symfony\OpenApi\ResponseMetadata;
use Sunrise\Symfony\OpenApi\ResponseMetadataResolverInterface;
use Sunrise\Symfony\OpenApi\Tests\TestKit;

final class EmptyResponseOperationEnricherTest extends TestCase
{
    use TestKit;

    public function testEnrichOperation(): void
    {
        $operation = [];

        $responseMetadataResolver = $this->createMock(ResponseMetadataResolverInterface::class);
        $responseMetadataResolver->method('resolveResponseMetadata')->willReturn(new ResponseMetadata(202, []));

        $operationEnricher = new EmptyResponseOperationEnricher($responseMetadataResolver);
        $operationEnricher->setOpenApiConfiguration(self::createOpenApiConfiguration());
        $operationEnricher->enrichOperation(
            self::createRouteAdapter(),
            self::createControllerReflection('emptyResponse'),
            $operation,
        );

        self::assertSame([
            202 => [
                'description' => 'The operation was successful.',
            ],
        ], $operation['responses']);
    }

    public function testDefaultStatus(): void
    {
        $operation = [];

        $responseMetadataResolver = $this->createMock(ResponseMetadataResolverInterface::class);
        $responseMetadataResolver->method('resolveResponseMetadata')->willReturn(new ResponseMetadata(null, []));

        $operationEnricher = new EmptyResponseOperationEnricher($responseMetadataResolver, 204);
        $operationEnricher->setOpenApiConfiguration(self::createOpenApiConfiguration());
        $operationEnricher->enrichOperation(
            self::createRouteAdapter(),
            self::createControllerReflection('emptyResponse'),
            $operation,
        );

        self::assertSame([
            204 => [
                'description' => 'The operation was successful.',
            ],
        ], $operation['responses']);
    }

    public function testNonVoidResponse(): void
    {
        $operation = [];

        $operationEnricher = new EmptyResponseOperationEnricher(
            $this->createMock(ResponseMetadataResolverInterface::class),
        );
        $operationEnricher->setOpenApiConfiguration(self::createOpenApiConfiguration());
        $operationEnricher->enrichOperation(
            self::createRouteAdapter(),
            self::createControllerReflection('serializableResponse'),
            $operation,
        );

        self::assertSame([], $operation);
    }
}
