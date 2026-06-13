<?php

declare(strict_types=1);

namespace Sunrise\Symfony\OpenApi\OperationEnricher;

use BackedEnum;
use DateTimeInterface;
use Override;
use ReflectionClass;
use ReflectionMethod;
use ReflectionNamedType;
use ReflectionParameter;
use Sunrise\Http\Router\OpenApi\OpenApiOperationEnricherInterface;
use Sunrise\Http\Router\OpenApi\OpenApiPhpTypeSchemaResolverManagerAwareInterface;
use Sunrise\Http\Router\OpenApi\OpenApiPhpTypeSchemaResolverManagerInterface;
use Sunrise\Http\Router\OpenApi\Type;
use Sunrise\Http\Router\OpenApi\TypeFactory;
use Sunrise\Http\Router\RouteInterface;
use Sunrise\Symfony\OpenApi\SymfonyRouteAwareInterface;
use Symfony\Component\Uid\AbstractUid;

final class PathVariablesOperationEnricher implements
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
        if (! $route instanceof SymfonyRouteAwareInterface) {
            return;
        }

        if (! $requestHandler instanceof ReflectionMethod) {
            return;
        }

        /** @var array<array-key, string> $variableNames */
        $variableNames = $route->getSymfonyRoute()->compile()->getPathVariables();
        /** @var array<string, string> $variablePatterns */
        $variablePatterns = $route->getSymfonyRoute()->getRequirements();

        /** @var array<string, ReflectionParameter> $requestHandlerParameters */
        $requestHandlerParameters = [];
        foreach ($requestHandler->getParameters() as $requestHandlerParameter) {
            $requestHandlerParameters[$requestHandlerParameter->name] = $requestHandlerParameter;
        }

        /** @var array<string, string|array{0: string, 1: string}> $mapping */
        $mapping = $route->getSymfonyRoute()->getDefault('_route_mapping') ?? [];

        foreach ($variableNames as $variableName) {
            $variableSchema = $this->getVariableSchema($variableName, $requestHandlerParameters, $mapping);
            $variableSchema ??= ['type' => Type::OAS_TYPE_NAME_STRING];

            if (isset($variablePatterns[$variableName])) {
                $variableSchema['pattern'] = '^' . $variablePatterns[$variableName] . '$';
            }

            $operation['parameters'][] = [
                'in' => 'path',
                'name' => $variableName,
                'schema' => $variableSchema,
                // https://github.com/OAI/OpenAPI-Specification/issues/93
                'required' => true,
            ];
        }
    }

    #[Override]
    public function getWeight(): int
    {
        return 40;
    }

    /**
     * @param array<string, ReflectionParameter> $parameters
     * @param array<string, string|array{0: string, 1: string}> $mapping
     *
     * @return array<array-key, mixed>|null
     */
    private function getVariableSchema(
        string $variableName,
        array $parameters,
        array $mapping,
    ): ?array {
        $parameter = self::getVariableParameter($variableName, $parameters, $mapping);
        if ($parameter === null) {
            return null;
        }

        if (!self::supportsVariableParameter($parameter)) {
            return null;
        }

        return $this->phpTypeSchemaResolverManager->resolvePhpTypeSchema(
            TypeFactory::fromPhpTypeReflection($parameter->getType()),
            $parameter,
        );
    }

    /**
     * @param array<string, ReflectionParameter> $parameters
     * @param array<string, string|array{0: string, 1: string}> $mapping
     */
    private static function getVariableParameter(
        string $variableName,
        array $parameters,
        array $mapping,
    ): ?ReflectionParameter {
        if (!isset($mapping[$variableName])) {
            return $parameters[$variableName] ?? null;
        }

        if (\is_string($mapping[$variableName])) {
            return $parameters[$mapping[$variableName]] ?? null;
        }

        // The {foo:bar.baz} notation is not supported.
        return null;
    }

    private static function supportsVariableParameter(ReflectionParameter $parameter): bool
    {
        $type = $parameter->getType();
        if (! $type instanceof ReflectionNamedType) {
            return false;
        }

        $typeName = $type->getName();

        return $typeName === Type::PHP_TYPE_NAME_BOOL
            || $typeName === Type::PHP_TYPE_NAME_INT
            || $typeName === Type::PHP_TYPE_NAME_FLOAT
            || $typeName === Type::PHP_TYPE_NAME_STRING
            || \is_subclass_of($typeName, BackedEnum::class, allow_string: true)
            || \is_a($typeName, DateTimeInterface::class, allow_string: true)
            || \is_subclass_of($typeName, AbstractUid::class, allow_string: true);
    }
}
