<?php

declare(strict_types=1);

namespace Sunrise\Symfony\OpenApi\OperationEnricher\ResponseOperationEnricher;

use ReflectionClass;
use ReflectionMethod;
use Sunrise\Http\Router\OpenApi\OpenApiConfiguration;
use Sunrise\Http\Router\OpenApi\OpenApiConfigurationAwareInterface;
use Sunrise\Http\Router\OpenApi\OpenApiOperationEnricherInterface;
use Sunrise\Http\Router\OpenApi\OpenApiPhpTypeSchemaResolverManagerAwareInterface;
use Sunrise\Http\Router\OpenApi\OpenApiPhpTypeSchemaResolverManagerInterface;
use Sunrise\Http\Router\OpenApi\Type;
use Sunrise\Http\Router\OpenApi\TypeFactory;
use Sunrise\Http\Router\RouteInterface;
use Sunrise\Symfony\OpenApi\ResponseMetadataResolverInterface;
use Sunrise\Symfony\OpenApi\SymfonyRouteAwareInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @since 1.0.0
 */
final class SerializableResponseOperationEnricher implements
    OpenApiOperationEnricherInterface,
    OpenApiConfigurationAwareInterface,
    OpenApiPhpTypeSchemaResolverManagerAwareInterface
{
    private OpenApiConfiguration $openApiConfiguration;
    private OpenApiPhpTypeSchemaResolverManagerInterface $openApiPhpTypeSchemaResolverManager;

    public function __construct(
        private readonly ResponseMetadataResolverInterface $responseMetadataResolver,
        private readonly int $defaultStatus,
        /** @var array<array-key, string> */
        private readonly array $defaultFormats,
    ) {
    }

    public function setOpenApiConfiguration(OpenApiConfiguration $openApiConfiguration): void
    {
        $this->openApiConfiguration = $openApiConfiguration;
    }

    public function setOpenApiPhpTypeSchemaResolverManager(
        OpenApiPhpTypeSchemaResolverManagerInterface $openApiPhpTypeSchemaResolverManager,
    ): void {
        $this->openApiPhpTypeSchemaResolverManager = $openApiPhpTypeSchemaResolverManager;
    }

    /**
     * @inheritDoc
     */
    public function enrichOperation(
        RouteInterface $route,
        ReflectionClass|ReflectionMethod $requestHandler,
        array &$operation,
    ): void {
        if (! $requestHandler instanceof ReflectionMethod) {
            return;
        }

        $returnType = TypeFactory::fromPhpTypeReflection($requestHandler->getReturnType());
        if (!self::supportsReturnType($returnType)) {
            return;
        }

        $status = null;
        $formats = [];

        if ($route instanceof SymfonyRouteAwareInterface) {
            $metadata = $this->responseMetadataResolver->resolveResponseMetadata(
                $route->getSymfonyRoute(),
                $requestHandler,
            );

            $status = $metadata->status;
            $formats = $metadata->formats;
        }

        $status = $status ?? $this->defaultStatus;
        $formats = $formats ?: $this->defaultFormats;

        $operation['responses'][$status] = [
            'description' => $this->openApiConfiguration->defaultResponseDescription,
        ];

        $contentSchema = $this->openApiPhpTypeSchemaResolverManager->resolvePhpTypeSchema(
            $returnType,
            $requestHandler,
        );

        foreach (self::getMimeTypesForFormats($formats) as $mimeType) {
            $operation['responses'][$status]['content'][$mimeType] = [
                'schema' => $contentSchema,
            ];
        }
    }

    public function getWeight(): int
    {
        return 20;
    }

    private static function supportsReturnType(Type $type): bool
    {
        if ($type->is(Type::PHP_TYPE_NAME_VOID)) {
            return false;
        }

        if (\is_a($type->name, Response::class, allow_string: true)) {
            return false;
        }

        return true;
    }

    /**
     * @param array<array-key, string> $formats
     *
     * @return array<array-key, string>
     */
    private static function getMimeTypesForFormats(array $formats): array
    {
        $result = [];
        foreach ($formats as $format) {
            $mimeTypes = Request::getMimeTypes($format);
            if (isset($mimeTypes[0])) {
                $result[] = $mimeTypes[0];
            }
        }

        return $result;
    }
}
