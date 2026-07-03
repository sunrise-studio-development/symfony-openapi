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

            $mapQueryStringArgs = $annotations[0]->getArguments();

            // Symfony less than 7.3 doesn't contain this argument.
            // https://github.com/symfony/symfony/blob/7.3/src/Symfony/Component/HttpKernel/CHANGELOG.md
            // https://symfony.com/blog/new-in-symfony-7-3-dx-improvements-part-2#improved-mapquerystring
            /** @var string|null $mapQueryStringKeyArg */
            $mapQueryStringKeyArg = $mapQueryStringArgs['key'] ?? null;

            $operation['parameters'][] = [
                'in' => 'query',
                'name' => $mapQueryStringKeyArg ?? $requestHandlerParameter->name,
                'schema' => $this->phpTypeSchemaResolverManager->resolvePhpTypeSchema(
                    TypeFactory::fromPhpTypeReflection($requestHandlerParameter->getType()),
                    $requestHandlerParameter,
                ),
                'required' => !$requestHandlerParameter->isDefaultValueAvailable()
                    && !$requestHandlerParameter->allowsNull(),
                // https://swagger.io/docs/specification/v3_0/serialization/#query-parameters
                'style' => $mapQueryStringKeyArg === null ? 'form' : 'deepObject',
            ];
        }
    }

    #[Override]
    public function getWeight(): int
    {
        return 20;
    }
}
