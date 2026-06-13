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
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;

final class MapQueryParameterOperationEnricher implements
    OpenApiOperationEnricherInterface,
    OpenApiPhpTypeSchemaResolverManagerAwareInterface
{
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
            /** @var list<ReflectionAttribute<MapQueryParameter>> $annotations */
            $annotations = $requestHandlerParameter->getAttributes(MapQueryParameter::class);
            if ($annotations === []) {
                continue;
            }

            $mapQueryParameter = $annotations[0]->newInstance();

            $queryParameterSchema = $this->phpTypeSchemaResolverManager->resolvePhpTypeSchema(
                TypeFactory::fromPhpTypeReflection($requestHandlerParameter->getType()),
                $requestHandlerParameter,
            );

            if ($requestHandlerParameter->isVariadic()) {
                $queryParameterSchema = [
                    'type' => Type::OAS_TYPE_NAME_ARRAY,
                    'items' => $queryParameterSchema,
                ];
            }

            $operation['parameters'][] = [
                'in' => 'query',
                'name' => $mapQueryParameter->name ?? $requestHandlerParameter->name,
                'schema' => $queryParameterSchema,
                'required' => !$requestHandlerParameter->isVariadic()
                    && !$requestHandlerParameter->isDefaultValueAvailable()
                    && !$requestHandlerParameter->allowsNull(),
            ];
        }
    }

    #[Override]
    public function getWeight(): int
    {
        return 30;
    }
}
