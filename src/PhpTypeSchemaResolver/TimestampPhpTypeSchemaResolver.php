<?php

declare(strict_types=1);

namespace Sunrise\Symfony\OpenApi\PhpTypeSchemaResolver;

use Override;
use ReflectionAttribute;
use ReflectionParameter;
use Reflector;
use Sunrise\Http\Router\OpenApi\OpenApiConfiguration;
use Sunrise\Http\Router\OpenApi\OpenApiConfigurationAwareInterface;
use Sunrise\Http\Router\OpenApi\OpenApiPhpTypeSchemaResolverInterface;
use Sunrise\Http\Router\OpenApi\PhpTypeSchemaResolver\TimestampPhpTypeSchemaResolver as SunrisePhpTypeSchemaResolver;
use Sunrise\Http\Router\OpenApi\TypeInterface;
use Symfony\Component\HttpKernel\Attribute\MapDateTime;

final readonly class TimestampPhpTypeSchemaResolver implements
    OpenApiPhpTypeSchemaResolverInterface,
    OpenApiConfigurationAwareInterface
{
    public function __construct(
        private SunrisePhpTypeSchemaResolver $sunrisePhpTypeSchemaResolver,
    ) {
    }

    #[Override]
    public function setOpenApiConfiguration(OpenApiConfiguration $openApiConfiguration): void
    {
        $this->sunrisePhpTypeSchemaResolver->setOpenApiConfiguration($openApiConfiguration);
    }

    #[Override]
    public function supportsPhpType(TypeInterface $phpType, Reflector $phpTypeHolder): bool
    {
        return $this->sunrisePhpTypeSchemaResolver->supportsPhpType($phpType, $phpTypeHolder);
    }

    /**
     * @inheritDoc
     */
    #[Override]
    public function resolvePhpTypeSchema(TypeInterface $phpType, Reflector $phpTypeHolder): array
    {
        $phpTypeSchema = $this->sunrisePhpTypeSchemaResolver->resolvePhpTypeSchema($phpType, $phpTypeHolder);

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

    #[Override]
    public function getWeight(): int
    {
        return $this->sunrisePhpTypeSchemaResolver->getWeight();
    }
}
