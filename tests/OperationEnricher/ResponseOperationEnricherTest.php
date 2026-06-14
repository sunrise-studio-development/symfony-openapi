<?php

declare(strict_types=1);

namespace Sunrise\Symfony\OpenApi\Tests\OperationEnricher;

use PHPUnit\Framework\TestCase;
use ReflectionClass;
use stdClass;
use Sunrise\Http\Router\OpenApi\OpenApiPhpTypeSchemaResolverManagerInterface;
use Sunrise\Http\Router\OpenApi\TypeInterface;
use Sunrise\Http\Router\RouteInterface;
use Sunrise\Symfony\OpenApi\OperationEnricher\ResponseOperationEnricher;
use Sunrise\Symfony\OpenApi\ResponseMetadata;
use Sunrise\Symfony\OpenApi\ResponseMetadataResolverInterface;
use Sunrise\Symfony\OpenApi\Tests\Fixture\DtoFixture;
use Sunrise\Symfony\OpenApi\Tests\TestKit;
use Symfony\Component\HttpKernel\Attribute\Serialize;
use Symfony\Component\Routing\Route;

final class ResponseOperationEnricherTest extends TestCase
{
    use TestKit;

    public function testSerializableResponse(): void
    {
        if (!\class_exists(Serialize::class)) {
            self::markTestSkipped('Symfony Serialize attribute is not available.');
        }

        $operation = [];
        $route = self::createRouteAdapter();
        $resolver = $this->mockResponseMetadataResolver(
            new ResponseMetadata(null, ['xml']),
            $route->getSymfonyRoute(),
            'serializableResponse',
        );
        $manager = $this->mockResponseSchemaResolverManager(['type' => 'object']);

        $operationEnricher = new ResponseOperationEnricher($resolver);
        $operationEnricher->setOpenApiConfiguration(self::createOpenApiConfiguration());
        $operationEnricher->setOpenApiPhpTypeSchemaResolverManager($manager);
        $operationEnricher->enrichOperation(
            $route,
            self::createControllerReflection('serializableResponse'),
            $operation,
        );

        self::assertSame([
            201 => [
                'description' => 'The operation was successful.',
                'content' => [
                    'text/xml' => [
                        'schema' => ['type' => 'object', 'phpType' => DtoFixture::class],
                    ],
                ],
            ],
        ], $operation['responses']);
    }

    public function testDefaultSerializableResponse(): void
    {
        $operation = [];
        $route = self::createRouteAdapter();
        $resolver = $this->mockResponseMetadataResolver(
            new ResponseMetadata(null, []),
            $route->getSymfonyRoute(),
            'defaultSerializableResponse',
        );
        $manager = $this->mockResponseSchemaResolverManager(['type' => 'object']);

        $operationEnricher = new ResponseOperationEnricher($resolver);
        $operationEnricher->setOpenApiConfiguration(self::createOpenApiConfiguration());
        $operationEnricher->setOpenApiPhpTypeSchemaResolverManager($manager);
        $operationEnricher->enrichOperation(
            $route,
            self::createControllerReflection('defaultSerializableResponse'),
            $operation,
        );

        self::assertSame([
            200 => [
                'description' => 'The operation was successful.',
                'content' => [
                    'application/json' => [
                        'schema' => ['type' => 'object', 'phpType' => DtoFixture::class],
                    ],
                ],
            ],
        ], $operation['responses']);
    }

    public function testResponseCodeOption(): void
    {
        $operation = [];
        $route = self::createRouteAdapter();
        $resolver = $this->mockResponseMetadataResolver(
            new ResponseMetadata(202, []),
            $route->getSymfonyRoute(),
            'defaultSerializableResponse',
        );
        $manager = $this->mockResponseSchemaResolverManager(['type' => 'object']);

        $operationEnricher = new ResponseOperationEnricher($resolver);
        $operationEnricher->setOpenApiConfiguration(self::createOpenApiConfiguration());
        $operationEnricher->setOpenApiPhpTypeSchemaResolverManager($manager);
        $operationEnricher->enrichOperation(
            $route,
            self::createControllerReflection('defaultSerializableResponse'),
            $operation,
        );

        self::assertSame([
            202 => [
                'description' => 'The operation was successful.',
                'content' => [
                    'application/json' => [
                        'schema' => ['type' => 'object', 'phpType' => DtoFixture::class],
                    ],
                ],
            ],
        ], $operation['responses']);
    }

    public function testEmptyResponse(): void
    {
        $operation = [];
        $route = self::createRouteAdapter();
        $resolver = $this->mockResponseMetadataResolver(
            new ResponseMetadata(202, ['json']),
            $route->getSymfonyRoute(),
            'emptyResponse',
        );

        $operationEnricher = new ResponseOperationEnricher($resolver);
        $operationEnricher->setOpenApiConfiguration(self::createOpenApiConfiguration());
        $operationEnricher->setOpenApiPhpTypeSchemaResolverManager($this->mockUnusedResponseSchemaResolverManager());
        $operationEnricher->enrichOperation(
            $route,
            self::createControllerReflection('emptyResponse'),
            $operation,
        );

        self::assertSame([
            202 => [
                'description' => 'The operation was successful.',
            ],
        ], $operation['responses']);
    }

    public function testDefaultEmptyResponse(): void
    {
        $operation = [];
        $route = self::createRouteAdapter();
        $resolver = $this->mockResponseMetadataResolver(
            new ResponseMetadata(null, []),
            $route->getSymfonyRoute(),
            'emptyResponse',
        );

        $operationEnricher = new ResponseOperationEnricher($resolver);
        $operationEnricher->setOpenApiConfiguration(self::createOpenApiConfiguration());
        $operationEnricher->setOpenApiPhpTypeSchemaResolverManager($this->mockUnusedResponseSchemaResolverManager());
        $operationEnricher->enrichOperation(
            $route,
            self::createControllerReflection('emptyResponse'),
            $operation,
        );

        self::assertSame([
            204 => [
                'description' => 'The operation was successful.',
            ],
        ], $operation['responses']);
    }

    public function testSymfonyResponse(): void
    {
        $operation = [];

        $operationEnricher = new ResponseOperationEnricher($this->mockUnusedResponseMetadataResolver());
        $operationEnricher->setOpenApiConfiguration(self::createOpenApiConfiguration());
        $operationEnricher->setOpenApiPhpTypeSchemaResolverManager($this->mockUnusedResponseSchemaResolverManager());
        $operationEnricher->enrichOperation(
            self::createRouteAdapter(),
            self::createControllerReflection('symfonyResponse'),
            $operation,
        );

        self::assertSame([], $operation);
    }

    public function testNonSymfonyRoute(): void
    {
        $operation = [];

        $operationEnricher = new ResponseOperationEnricher($this->mockUnusedResponseMetadataResolver());
        $operationEnricher->setOpenApiConfiguration(self::createOpenApiConfiguration());
        $operationEnricher->setOpenApiPhpTypeSchemaResolverManager($this->mockUnusedResponseSchemaResolverManager());
        $operationEnricher->enrichOperation(
            $this->createStub(RouteInterface::class),
            self::createControllerReflection('emptyResponse'),
            $operation,
        );

        self::assertSame([], $operation);
    }

    public function testNonMethodRequestHandler(): void
    {
        $operation = [];

        $operationEnricher = new ResponseOperationEnricher($this->mockUnusedResponseMetadataResolver());
        $operationEnricher->setOpenApiConfiguration(self::createOpenApiConfiguration());
        $operationEnricher->setOpenApiPhpTypeSchemaResolverManager($this->mockUnusedResponseSchemaResolverManager());
        $operationEnricher->enrichOperation(
            self::createRouteAdapter(),
            new ReflectionClass(new stdClass()),
            $operation,
        );

        self::assertSame([], $operation);
    }

    public function testWeight(): void
    {
        self::assertSame(20, (new ResponseOperationEnricher($this->mockUnusedResponseMetadataResolver()))->getWeight());
    }

    private function mockResponseMetadataResolver(
        ResponseMetadata $metadata,
        Route $route,
        string $controller,
    ): ResponseMetadataResolverInterface {
        $resolver = $this->createMock(ResponseMetadataResolverInterface::class);
        $resolver
            ->expects($this->once())
            ->method('resolveResponseMetadata')
            ->with($this->identicalTo($route), self::createControllerReflection($controller))
            ->willReturn($metadata);

        return $resolver;
    }

    private function mockUnusedResponseMetadataResolver(): ResponseMetadataResolverInterface
    {
        $resolver = $this->createMock(ResponseMetadataResolverInterface::class);
        $resolver
            ->expects($this->never())
            ->method('resolveResponseMetadata');

        return $resolver;
    }

    /**
     * @param array<array-key, mixed> $schema
     */
    private function mockResponseSchemaResolverManager(array $schema): OpenApiPhpTypeSchemaResolverManagerInterface
    {
        $manager = $this->createMock(OpenApiPhpTypeSchemaResolverManagerInterface::class);
        $manager
            ->expects($this->once())
            ->method('resolvePhpTypeSchema')
            ->willReturnCallback(static function (TypeInterface $type) use ($schema): array {
                return $schema + ['phpType' => $type->getName()];
            });

        return $manager;
    }

    private function mockUnusedResponseSchemaResolverManager(): OpenApiPhpTypeSchemaResolverManagerInterface
    {
        $manager = $this->createMock(OpenApiPhpTypeSchemaResolverManagerInterface::class);
        $manager
            ->expects($this->never())
            ->method('resolvePhpTypeSchema');

        return $manager;
    }
}
