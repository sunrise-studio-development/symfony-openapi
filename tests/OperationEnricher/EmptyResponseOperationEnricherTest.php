<?php

declare(strict_types=1);

namespace Sunrise\Symfony\OpenApi\Tests\OperationEnricher;

use PHPUnit\Framework\TestCase;
use ReflectionClass;
use stdClass;
use Sunrise\Symfony\OpenApi\OperationEnricher\EmptyResponseOperationEnricher;
use Sunrise\Symfony\OpenApi\Tests\TestKit;

final class EmptyResponseOperationEnricherTest extends TestCase
{
    use TestKit;

    public function testEnrichOperation(): void
    {
        $operation = [];

        $operationEnricher = new EmptyResponseOperationEnricher();
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

    public function testCustomDefaultStatusCode(): void
    {
        $operation = [];

        $operationEnricher = new EmptyResponseOperationEnricher(202);
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

    public function testNonVoidResponse(): void
    {
        $operation = [];

        $operationEnricher = new EmptyResponseOperationEnricher();
        $operationEnricher->setOpenApiConfiguration(self::createOpenApiConfiguration());
        $operationEnricher->enrichOperation(
            self::createRouteAdapter(),
            self::createControllerReflection('serializableResponse'),
            $operation,
        );

        self::assertSame([], $operation);
    }

    public function testNonMethodRequestHandler(): void
    {
        $operation = [];

        $operationEnricher = new EmptyResponseOperationEnricher();
        $operationEnricher->setOpenApiConfiguration(self::createOpenApiConfiguration());
        $operationEnricher->enrichOperation(
            self::createRouteAdapter(),
            new ReflectionClass(new stdClass()),
            $operation,
        );

        self::assertSame([], $operation);
    }

    public function testWeight(): void
    {
        self::assertSame(20, (new EmptyResponseOperationEnricher())->getWeight());
    }
}
