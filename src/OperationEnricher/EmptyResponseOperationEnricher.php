<?php

declare(strict_types=1);

namespace Sunrise\Symfony\OpenApi\OperationEnricher;

use Override;
use ReflectionClass;
use ReflectionMethod;
use Sunrise\Http\Router\OpenApi\OpenApiConfiguration;
use Sunrise\Http\Router\OpenApi\OpenApiConfigurationAwareInterface;
use Sunrise\Http\Router\OpenApi\OpenApiOperationEnricherInterface;
use Sunrise\Http\Router\OpenApi\Type;
use Sunrise\Http\Router\OpenApi\TypeFactory;
use Sunrise\Http\Router\RouteInterface;

final class EmptyResponseOperationEnricher implements
    OpenApiOperationEnricherInterface,
    OpenApiConfigurationAwareInterface
{
    private const DEFAULT_RESPONSE_STATUS_CODE = 204;

    private OpenApiConfiguration $openApiConfiguration;

    public function __construct(
        private readonly int $defaultResponseStatusCode = self::DEFAULT_RESPONSE_STATUS_CODE,
    ) {
    }

    #[Override]
    public function setOpenApiConfiguration(OpenApiConfiguration $openApiConfiguration): void
    {
        $this->openApiConfiguration = $openApiConfiguration;
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

        $responseType = TypeFactory::fromPhpTypeReflection($requestHandler->getReturnType());
        if ($responseType->getName() !== Type::PHP_TYPE_NAME_VOID) {
            return;
        }

        $operation['responses'][$this->defaultResponseStatusCode] = [
            'description' => $this->openApiConfiguration->defaultResponseDescription,
        ];
    }

    #[Override]
    public function getWeight(): int
    {
        return 20;
    }
}
