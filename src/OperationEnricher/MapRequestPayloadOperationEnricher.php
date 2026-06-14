<?php

declare(strict_types=1);

namespace Sunrise\Symfony\OpenApi\OperationEnricher;

use Override;
use ReflectionAttribute;
use ReflectionClass;
use ReflectionMethod;
use Sunrise\Http\Router\OpenApi\OpenApiOperationEnricherInterface;
use Sunrise\Http\Router\OpenApi\OpenApiPhpTypeSchemaResolverManagerAwareInterface;
use Sunrise\Http\Router\OpenApi\OpenApiPhpTypeSchemaResolverManagerInterface;
use Sunrise\Http\Router\OpenApi\Type;
use Sunrise\Http\Router\OpenApi\TypeFactory;
use Sunrise\Http\Router\RouteInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;

final class MapRequestPayloadOperationEnricher implements
    OpenApiOperationEnricherInterface,
    OpenApiPhpTypeSchemaResolverManagerAwareInterface
{
    private const DEFAULT_REQUEST_FORMATS = ['json'];

    private OpenApiPhpTypeSchemaResolverManagerInterface $phpTypeSchemaResolverManager;

    #[Override]
    public function setOpenApiPhpTypeSchemaResolverManager(
        OpenApiPhpTypeSchemaResolverManagerInterface $openApiPhpTypeSchemaResolverManager,
    ): void {
        $this->phpTypeSchemaResolverManager = $openApiPhpTypeSchemaResolverManager;
    }

    /**
     * @inheritDoc
     */
    #[Override]
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

            $requestBodyType = TypeFactory::fromPhpTypeReflection($requestHandlerParameter->getType());

            if ($mapRequestPayload->type !== null && $requestBodyType->getName() === Type::PHP_TYPE_NAME_ARRAY) {
                $requestBodySchema = [
                    'type' => Type::OAS_TYPE_NAME_ARRAY,
                    'items' => $this->phpTypeSchemaResolverManager->resolvePhpTypeSchema(
                        new Type($mapRequestPayload->type),
                        $requestHandlerParameter,
                    )
                ];
            } else {
                $requestBodySchema = $this->phpTypeSchemaResolverManager->resolvePhpTypeSchema(
                    $requestBodyType,
                    $requestHandlerParameter,
                );
            }

            /** @var string|null $routeFormat */
            $routeFormat = $route->getAttribute('_format');

            $requestFormats = (array) $mapRequestPayload->acceptFormat
                ?: (array) $routeFormat
                ?: self::DEFAULT_REQUEST_FORMATS;

            foreach (self::getMediaTypesForFormats($requestFormats) as $mediaType) {
                $operation['requestBody']['content'][$mediaType]['schema'] = $requestBodySchema;
            }

            if (!$requestHandlerParameter->isDefaultValueAvailable() && !$requestHandlerParameter->allowsNull()) {
                $operation['requestBody']['required'] = true;
            }

            break;
        }
    }

    #[Override]
    public function getWeight(): int
    {
        return 10;
    }

    /**
     * @param array<array-key, string> $formats
     *
     * @return array<array-key, string>
     */
    private static function getMediaTypesForFormats(array $formats): array
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
