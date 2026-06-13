<?php

declare(strict_types=1);

namespace Sunrise\Symfony\ApiFoundation\OperationEnricher;

use ReflectionAttribute;
use ReflectionClass;
use ReflectionMethod;
use Sunrise\Http\Router\OpenApi\OpenApiConfiguration;
use Sunrise\Http\Router\OpenApi\OpenApiConfigurationAwareInterface;
use Sunrise\Http\Router\OpenApi\OpenApiOperationEnricherInterface;
use Sunrise\Http\Router\OpenApi\OpenApiPhpTypeSchemaResolverManagerAwareInterface;
use Sunrise\Http\Router\OpenApi\OpenApiPhpTypeSchemaResolverManagerInterface;
use Sunrise\Http\Router\OpenApi\TypeFactory;
use Sunrise\Http\Router\RouteInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\Serialize;

final class SerializableResponseOperationEnricher implements
    OpenApiOperationEnricherInterface,
    OpenApiConfigurationAwareInterface,
    OpenApiPhpTypeSchemaResolverManagerAwareInterface
{
    private const DEFAULT_RESPONSE_FORMAT = 'json';
    private const FALLBACK_RESPONSE_MEDIA_TYPE = 'application/json';

    private OpenApiConfiguration $openApiConfiguration;
    private OpenApiPhpTypeSchemaResolverManagerInterface $phpTypeSchemaResolverManager;

    public function setOpenApiConfiguration(OpenApiConfiguration $openApiConfiguration): void
    {
        $this->openApiConfiguration = $openApiConfiguration;
    }

    public function setOpenApiPhpTypeSchemaResolverManager(
        OpenApiPhpTypeSchemaResolverManagerInterface $openApiPhpTypeSchemaResolverManager,
    ): void {
        $this->phpTypeSchemaResolverManager = $openApiPhpTypeSchemaResolverManager;
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

        /** @var list<ReflectionAttribute<Serialize>> $serializeReflectionAttributes */
        $serializeReflectionAttributes = $requestHandler->getAttributes(Serialize::class);
        if ($serializeReflectionAttributes === []) {
            return;
        }

        $serializeAttribute = $serializeReflectionAttributes[0]->newInstance();

        /** @var string $responseFormat */
        $responseFormat = $route->getAttribute('_format') ?? self::DEFAULT_RESPONSE_FORMAT;
        $responseMediaType = Request::getMimeTypes($responseFormat)[0] ?? self::FALLBACK_RESPONSE_MEDIA_TYPE;

        $operation['responses'][$serializeAttribute->code] = [
            'description' => $this->openApiConfiguration->defaultResponseDescription,
            'content' => [
                $responseMediaType => [
                    'schema' => $this->phpTypeSchemaResolverManager->resolvePhpTypeSchema(
                        TypeFactory::fromPhpTypeReflection($requestHandler->getReturnType()),
                        $requestHandler,
                    ),
                ],
            ],
        ];
    }

    public function getWeight(): int
    {
        return 20;
    }
}
