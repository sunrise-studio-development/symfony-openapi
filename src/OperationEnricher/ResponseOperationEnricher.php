<?php

declare(strict_types=1);

namespace Sunrise\Symfony\OpenApi\OperationEnricher;

use Override;
use ReflectionAttribute;
use ReflectionClass;
use ReflectionMethod;
use Sunrise\Http\Router\OpenApi\OpenApiConfiguration;
use Sunrise\Http\Router\OpenApi\OpenApiConfigurationAwareInterface;
use Sunrise\Http\Router\OpenApi\OpenApiOperationEnricherInterface;
use Sunrise\Http\Router\OpenApi\OpenApiPhpTypeSchemaResolverManagerAwareInterface;
use Sunrise\Http\Router\OpenApi\OpenApiPhpTypeSchemaResolverManagerInterface;
use Sunrise\Http\Router\OpenApi\Type;
use Sunrise\Http\Router\OpenApi\TypeFactory;
use Sunrise\Http\Router\RouteInterface;
use Sunrise\Symfony\OpenApi\ResponseMetadataResolverInterface;
use Sunrise\Symfony\OpenApi\SymfonyRouteAwareInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\Serialize;

final class ResponseOperationEnricher implements
    OpenApiOperationEnricherInterface,
    OpenApiConfigurationAwareInterface,
    OpenApiPhpTypeSchemaResolverManagerAwareInterface
{
    private const DEFAULT_EMPTY_RESPONSE_CODE = 204;
    private const DEFAULT_SERIALIZED_RESPONSE_CODE = 200;
    private const DEFAULT_SERIALIZED_RESPONSE_FORMATS = ['json'];

    private OpenApiConfiguration $openApiConfiguration;
    private OpenApiPhpTypeSchemaResolverManagerInterface $phpTypeSchemaResolverManager;

    public function __construct(
        private readonly ResponseMetadataResolverInterface $responseMetadataResolver,
    ) {
    }

    #[Override]
    public function setOpenApiConfiguration(OpenApiConfiguration $openApiConfiguration): void
    {
        $this->openApiConfiguration = $openApiConfiguration;
    }

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

        if (! $route instanceof SymfonyRouteAwareInterface) {
            return;
        }

        $responseType = TypeFactory::fromPhpTypeReflection($requestHandler->getReturnType());
        if (\is_a($responseType->getName(), Response::class, allow_string: true)) {
            return;
        }

        $responseMetadata = $this->responseMetadataResolver->resolveResponseMetadata(
            $route->getSymfonyRoute(),
            $requestHandler,
        );

        $responseDescription = $this->openApiConfiguration->defaultResponseDescription;

        // an empty response
        if ($responseType->getName() === Type::PHP_TYPE_NAME_VOID) {
            $responseCode = $responseMetadata->code ?? self::DEFAULT_EMPTY_RESPONSE_CODE;
            $operation['responses'][$responseCode]['description'] = $responseDescription;
            return;
        }

        $responseCode = self::getResponseCodeFromSerializeAttribute($requestHandler)
            ?? $responseMetadata->code
            ?? self::DEFAULT_SERIALIZED_RESPONSE_CODE;

        $operation['responses'][$responseCode]['description'] = $responseDescription;
        $responseFormats = $responseMetadata->formats ?: self::DEFAULT_SERIALIZED_RESPONSE_FORMATS;
        $responseSchema = $this->phpTypeSchemaResolverManager->resolvePhpTypeSchema($responseType, $requestHandler);
        foreach (self::getMediaTypesForFormats($responseFormats) as $mediaType) {
            $operation['responses'][$responseCode]['content'][$mediaType]['schema'] = $responseSchema;
        }
    }

    #[Override]
    public function getWeight(): int
    {
        return 20;
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

    /**
     * @link https://symfony.com/blog/new-in-symfony-8-1-serialize-attribute
     */
    private static function getResponseCodeFromSerializeAttribute(ReflectionMethod $controller): ?int
    {
        /** @var list<ReflectionAttribute<Serialize>> $attributes */
        $attributes = $controller->getAttributes(Serialize::class);
        if (isset($attributes[0])) {
            $attribute = $attributes[0]->newInstance();
            return $attribute->code;
        }

        return null;
    }
}
