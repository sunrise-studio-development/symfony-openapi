<?php

declare(strict_types=1);

namespace Sunrise\Symfony\OpenApi\OperationEnricher\ResponseOperationEnricher;

use ReflectionClass;
use ReflectionMethod;
use Sunrise\Http\Router\OpenApi\OpenApiConfiguration;
use Sunrise\Http\Router\OpenApi\OpenApiConfigurationAwareInterface;
use Sunrise\Http\Router\OpenApi\OpenApiOperationEnricherInterface;
use Sunrise\Http\Router\OpenApi\Type;
use Sunrise\Http\Router\OpenApi\TypeFactory;
use Sunrise\Http\Router\RouteInterface;
use Sunrise\Symfony\OpenApi\ResponseMetadataResolverInterface;
use Sunrise\Symfony\OpenApi\SymfonyRouteAwareInterface;

/**
 * @since 1.0.0
 */
final class EmptyResponseOperationEnricher implements
    OpenApiOperationEnricherInterface,
    OpenApiConfigurationAwareInterface
{
    private OpenApiConfiguration $openApiConfiguration;

    public function __construct(
        private readonly ResponseMetadataResolverInterface $responseMetadataResolver,
        private readonly int $defaultStatus,
    ) {
    }

    public function setOpenApiConfiguration(OpenApiConfiguration $openApiConfiguration): void
    {
        $this->openApiConfiguration = $openApiConfiguration;
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
        if (!$returnType->is(Type::PHP_TYPE_NAME_VOID)) {
            return;
        }

        $status = null;

        if ($route instanceof SymfonyRouteAwareInterface) {
            $metadata = $this->responseMetadataResolver->resolveResponseMetadata(
                $route->getSymfonyRoute(),
                $requestHandler,
            );

            $status = $metadata->status;
        }

        $status ??= $this->defaultStatus;

        $operation['responses'][$status] = [
            'description' => $this->openApiConfiguration->defaultResponseDescription,
        ];
    }

    public function getWeight(): int
    {
        return 10;
    }
}
