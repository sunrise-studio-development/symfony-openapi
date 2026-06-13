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
use Sunrise\Http\Router\OpenApi\TypeFactory;
use Sunrise\Http\Router\RouteInterface;
use Symfony\Component\HttpKernel\Attribute\MapQueryString;

final class MapQueryStringOperationEnricher implements
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
            /** @var list<ReflectionAttribute<MapQueryString>> $annotations */
            $annotations = $requestHandlerParameter->getAttributes(MapQueryString::class);
            if ($annotations === []) {
                continue;
            }

            $mapQueryString = $annotations[0]->newInstance();

            $operation['parameters'][] = [
                'in' => 'query',
                'name' => $mapQueryString->key ?? $requestHandlerParameter->name,
                'schema' => $this->phpTypeSchemaResolverManager->resolvePhpTypeSchema(
                    TypeFactory::fromPhpTypeReflection($requestHandlerParameter->getType()),
                    $requestHandlerParameter,
                ),
                'required' => !$requestHandlerParameter->isDefaultValueAvailable()
                    && !$requestHandlerParameter->allowsNull(),
                // https://swagger.io/docs/specification/v3_0/serialization/#query-parameters
                'style' => $mapQueryString->key === null ? 'form' : 'deepObject',
            ];
        }
    }

    #[Override]
    public function getWeight(): int
    {
        return 20;
    }
}
