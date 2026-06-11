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
    public const DEFAULT_STATUS = 200;

    /**
     * @var array<array-key, string>
     */
    public const DEFAULT_FORMATS = ['json'];

    private OpenApiConfiguration $openApiConfiguration;
    private OpenApiPhpTypeSchemaResolverManagerInterface $openApiPhpTypeSchemaResolverManager;

    public function __construct(
        private readonly ResponseMetadataResolverInterface $responseMetadataResolver,
        private readonly int $defaultStatus = self::DEFAULT_STATUS,
        /** @var array<array-key, string> */
        private readonly array $defaultFormats = self::DEFAULT_FORMATS,
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

        $responseStatus = null;
        $responseFormats = [];

        if ($route instanceof SymfonyRouteAwareInterface) {
            $responseMetadata = $this->responseMetadataResolver->resolveResponseMetadata(
                $route->getSymfonyRoute(),
                $requestHandler,
            );

            $responseStatus = $responseMetadata->status;
            $responseFormats = $responseMetadata->formats;
        }

        $responseStatus = $responseStatus ?? $this->defaultStatus;
        $responseFormats = $responseFormats ?: $this->defaultFormats;

        $operation['responses'][$responseStatus] = [
            'description' => $this->openApiConfiguration->defaultResponseDescription,
        ];

        $responseSchema = $this->openApiPhpTypeSchemaResolverManager->resolvePhpTypeSchema(
            $returnType,
            $requestHandler,
        );

        foreach (self::getMimeTypesForFormats($responseFormats) as $mimeType) {
            $operation['responses'][$responseStatus]['content'][$mimeType] = [
                'schema' => $responseSchema,
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
