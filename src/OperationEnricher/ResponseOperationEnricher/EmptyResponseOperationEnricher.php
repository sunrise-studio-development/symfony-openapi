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
    public const DEFAULT_STATUS = 204;

    private OpenApiConfiguration $openApiConfiguration;

    public function __construct(
        private readonly ResponseMetadataResolverInterface $responseMetadataResolver,
        private readonly int $defaultStatus = self::DEFAULT_STATUS,
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

        $responseStatus = null;

        if ($route instanceof SymfonyRouteAwareInterface) {
            $responseMetadata = $this->responseMetadataResolver->resolveResponseMetadata(
                $route->getSymfonyRoute(),
                $requestHandler,
            );

            $responseStatus = $responseMetadata->status;
        }

        $responseStatus ??= $this->defaultStatus;

        $operation['responses'][$responseStatus] = [
            'description' => $this->openApiConfiguration->defaultResponseDescription,
        ];
    }

    public function getWeight(): int
    {
        return 10;
    }
}
