<?php

declare(strict_types=1);

namespace Sunrise\Symfony\OpenApi\PhpTypeSchemaResolver;

use ReflectionAttribute;
use ReflectionParameter;
use Reflector;
use Sunrise\Http\Router\OpenApi\Exception\UnsupportedPhpTypeException;
use Sunrise\Http\Router\OpenApi\OpenApiConfiguration;
use Sunrise\Http\Router\OpenApi\OpenApiConfigurationAwareInterface;
use Sunrise\Http\Router\OpenApi\OpenApiPhpTypeSchemaResolverInterface;
use Sunrise\Http\Router\OpenApi\PhpTypeSchemaResolver\TimestampPhpTypeSchemaResolver as SunrisePhpTypeSchemaResolver;
use Sunrise\Http\Router\OpenApi\Type;
use Symfony\Component\HttpKernel\Attribute\MapDateTime;

final readonly class TimestampPhpTypeSchemaResolver implements
    OpenApiPhpTypeSchemaResolverInterface,
    OpenApiConfigurationAwareInterface
{
    public function __construct(
        private SunrisePhpTypeSchemaResolver $timestampPhpTypeSchemaResolver,
    ) {
    }

    public function setOpenApiConfiguration(OpenApiConfiguration $openApiConfiguration): void
    {
        $this->timestampPhpTypeSchemaResolver->setOpenApiConfiguration($openApiConfiguration);
    }

    public function supportsPhpType(Type $phpType, Reflector $phpTypeHolder): bool
    {
        return $this->timestampPhpTypeSchemaResolver->supportsPhpType($phpType, $phpTypeHolder);
    }

    /**
     * @inheritDoc
     */
    public function resolvePhpTypeSchema(Type $phpType, Reflector $phpTypeHolder): array
    {
        $this->supportsPhpType($phpType, $phpTypeHolder) or throw new UnsupportedPhpTypeException();

        $phpTypeSchema = $this->timestampPhpTypeSchemaResolver->resolvePhpTypeSchema($phpType, $phpTypeHolder);

        if ($phpTypeHolder instanceof ReflectionParameter) {
            /** @var list<ReflectionAttribute<MapDateTime>> $annotations */
            $annotations = $phpTypeHolder->getAttributes(MapDateTime::class);
            if (isset($annotations[0])) {
                $annotation = $annotations[0]->newInstance();
                if ($annotation->format !== null && !$annotation->disabled) {
                    $phpTypeSchema['example'] = \date($annotation->format, 0);
                }
            }
        }

        return $phpTypeSchema;
    }

    public function getWeight(): int
    {
        return $this->timestampPhpTypeSchemaResolver->getWeight();
    }
}
