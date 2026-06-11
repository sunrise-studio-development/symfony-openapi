<?php

declare(strict_types=1);

namespace Sunrise\Symfony\OpenApi\OperationEnricher;

use ReflectionAttribute;
use ReflectionClass;
use ReflectionMethod;
use ReflectionNamedType;
use Sunrise\Http\Router\OpenApi\OpenApiOperationEnricherInterface;
use Sunrise\Http\Router\OpenApi\OpenApiPhpTypeSchemaResolverManagerAwareInterface;
use Sunrise\Http\Router\OpenApi\OpenApiPhpTypeSchemaResolverManagerInterface;
use Sunrise\Http\Router\OpenApi\Type;
use Sunrise\Http\Router\OpenApi\TypeFactory;
use Sunrise\Http\Router\RouteInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;

/**
 * @since 1.0.0
 */
final class MapRequestPayloadOperationEnricher implements
    OpenApiOperationEnricherInterface,
    OpenApiPhpTypeSchemaResolverManagerAwareInterface
{
    /**
     * @var array<array-key, string>
     */
    public const DEFAULT_ACCEPT_FORMATS = ['json'];

    private OpenApiPhpTypeSchemaResolverManagerInterface $openApiPhpTypeSchemaResolverManager;

    public function __construct(
        /** @var array<array-key, string> */
        private readonly array $defaultAcceptFormats = self::DEFAULT_ACCEPT_FORMATS,
    ) {
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

        foreach ($requestHandler->getParameters() as $requestHandlerParameter) {
            /** @var list<ReflectionAttribute<MapRequestPayload>> $annotations */
            $annotations = $requestHandlerParameter->getAttributes(MapRequestPayload::class);
            if ($annotations === []) {
                continue;
            }

            $mapRequestPayload = $annotations[0]->newInstance();

            if (
                $mapRequestPayload->type !== null &&
                $requestHandlerParameter->getType() instanceof ReflectionNamedType &&
                $requestHandlerParameter->getType()->getName() === Type::PHP_TYPE_NAME_ARRAY
            ) {
                $requestBodySchema = [
                    'type' => Type::OAS_TYPE_NAME_ARRAY,
                    'items' => $this->openApiPhpTypeSchemaResolverManager->resolvePhpTypeSchema(
                        new Type($mapRequestPayload->type),
                        $requestHandlerParameter,
                    )
                ];
            } else {
                $requestBodySchema = $this->openApiPhpTypeSchemaResolverManager->resolvePhpTypeSchema(
                    TypeFactory::fromPhpTypeReflection($requestHandlerParameter->getType()),
                    $requestHandlerParameter,
                );
            }

            $acceptFormats = (array) $mapRequestPayload->acceptFormat ?: $this->defaultAcceptFormats;

            foreach (self::getMimeTypesForFormats($acceptFormats) as $mimeType) {
                $operation['requestBody']['content'][$mimeType] = [
                    'schema' => $requestBodySchema,
                ];
            }

            if (!$requestHandlerParameter->isDefaultValueAvailable() && !$requestHandlerParameter->allowsNull()) {
                $operation['requestBody']['required'] = true;
            }

            break;
        }
    }

    public function getWeight(): int
    {
        return 10;
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
