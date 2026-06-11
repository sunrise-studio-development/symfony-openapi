<?php

declare(strict_types=1);

use Sunrise\Coder\Codec\JsonCodec;
use Sunrise\Coder\CodecManager;
use Sunrise\Coder\CodecManagerInterface;
use Sunrise\Coder\Dictionary\MediaType;
use Sunrise\Http\Router\OpenApi\OpenApiConfiguration;
use Sunrise\Http\Router\OpenApi\OpenApiDocumentManager;
use Sunrise\Http\Router\OpenApi\OpenApiDocumentManagerInterface;
use Sunrise\Http\Router\OpenApi\OpenApiOperationEnricherManager;
use Sunrise\Http\Router\OpenApi\OpenApiOperationEnricherManagerInterface;
use Sunrise\Http\Router\OpenApi\OpenApiPathBuilderInterface;
use Sunrise\Http\Router\OpenApi\OpenApiPhpTypeSchemaResolverManager;
use Sunrise\Http\Router\OpenApi\OpenApiPhpTypeSchemaResolverManagerInterface;
use Sunrise\Http\Router\OpenApi\PhpTypeSchemaResolver\ArrayAccessPhpTypeSchemaResolver;
use Sunrise\Http\Router\OpenApi\PhpTypeSchemaResolver\ArrayPhpTypeSchemaResolver;
use Sunrise\Http\Router\OpenApi\PhpTypeSchemaResolver\BackedEnumPhpTypeSchemaResolver;
use Sunrise\Http\Router\OpenApi\PhpTypeSchemaResolver\BoolPhpTypeSchemaResolver;
use Sunrise\Http\Router\OpenApi\PhpTypeSchemaResolver\FloatPhpTypeSchemaResolver;
use Sunrise\Http\Router\OpenApi\PhpTypeSchemaResolver\IntPhpTypeSchemaResolver;
use Sunrise\Http\Router\OpenApi\PhpTypeSchemaResolver\ObjectPhpTypeSchemaResolver;
use Sunrise\Http\Router\OpenApi\PhpTypeSchemaResolver\StringPhpTypeSchemaResolver;
use Sunrise\Http\Router\OpenApi\PhpTypeSchemaResolver\SymfonyUidPhpTypeSchemaResolver;
use Sunrise\Http\Router\OpenApi\PhpTypeSchemaResolver\TimestampPhpTypeSchemaResolver
    as SunriseTimestampPhpTypeSchemaResolver;
use Sunrise\Http\Router\OpenApi\SwaggerConfiguration;
use Sunrise\Http\Router\RequestHandlerReflectorInterface;
use Sunrise\Symfony\OpenApi\Command\OpenApiBuildDocumentCommand;
use Sunrise\Symfony\OpenApi\Controller\OpenApiController;
use Sunrise\Symfony\OpenApi\Controller\SwaggerController;
use Sunrise\Symfony\OpenApi\OpenApiPathBuilder;
use Sunrise\Symfony\OpenApi\OperationEnricher\MapQueryParameterOperationEnricher;
use Sunrise\Symfony\OpenApi\OperationEnricher\MapQueryStringOperationEnricher;
use Sunrise\Symfony\OpenApi\OperationEnricher\MapRequestPayloadOperationEnricher;
use Sunrise\Symfony\OpenApi\OperationEnricher\MapUploadedFileOperationEnricher;
use Sunrise\Symfony\OpenApi\OperationEnricher\PathVariablesOperationEnricher;
use Sunrise\Symfony\OpenApi\PhpTypeSchemaResolver\TimestampPhpTypeSchemaResolver
    as SymfonyTimestampPhpTypeSchemaResolver;
use Sunrise\Symfony\OpenApi\RequestHandlerReflector;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

return static function (ContainerConfigurator $container): void {
    $services = $container->services();
    $services
        ->defaults()
        ->autowire()
        ->autoconfigure();

    // ***

    $services->set(OpenApiConfiguration::class)
        ->arg('$initialDocument', [
            'openapi' => OpenApiConfiguration::VERSION,
            'info' => [
                'title' => 'API',
                'version' => '1.0.0',
            ],
        ])
        ->arg('$initialOperation', [
            'responses' => [],
        ])
        ->arg('$documentMediaType', MediaType::JSON)
        ->arg('$documentFilename', '%kernel.project_dir%/var/openapi.json');

    $services->set(SwaggerConfiguration::class);

    // ***

    $services->set(ArrayAccessPhpTypeSchemaResolver::class);
    $services->set(ArrayPhpTypeSchemaResolver::class);
    $services->set(BackedEnumPhpTypeSchemaResolver::class);
    $services->set(BoolPhpTypeSchemaResolver::class);
    $services->set(FloatPhpTypeSchemaResolver::class);
    $services->set(IntPhpTypeSchemaResolver::class);
    $services->set(ObjectPhpTypeSchemaResolver::class);
    $services->set(StringPhpTypeSchemaResolver::class);
    $services->set(SunriseTimestampPhpTypeSchemaResolver::class);
    $services->set(SymfonyTimestampPhpTypeSchemaResolver::class);
    $services->set(SymfonyUidPhpTypeSchemaResolver::class);

    $services->set(OpenApiPhpTypeSchemaResolverManagerInterface::class, OpenApiPhpTypeSchemaResolverManager::class)
        ->arg('$phpTypeSchemaResolvers', [
            service(ArrayAccessPhpTypeSchemaResolver::class),
            service(ArrayPhpTypeSchemaResolver::class),
            service(BackedEnumPhpTypeSchemaResolver::class),
            service(BoolPhpTypeSchemaResolver::class),
            service(FloatPhpTypeSchemaResolver::class),
            service(IntPhpTypeSchemaResolver::class),
            service(ObjectPhpTypeSchemaResolver::class),
            service(StringPhpTypeSchemaResolver::class),
            service(SymfonyTimestampPhpTypeSchemaResolver::class),
            service(SymfonyUidPhpTypeSchemaResolver::class),
        ])
        ->arg('$useDefaultPhpTypeSchemaResolvers', false);

    // ***

    $services->set(MapQueryParameterOperationEnricher::class);
    $services->set(MapQueryStringOperationEnricher::class);
    $services->set(MapRequestPayloadOperationEnricher::class);
    $services->set(MapUploadedFileOperationEnricher::class);
    $services->set(PathVariablesOperationEnricher::class);

    $services->set(OpenApiOperationEnricherManagerInterface::class, OpenApiOperationEnricherManager::class)
        ->arg('$operationEnrichers', [
            service(MapQueryParameterOperationEnricher::class),
            service(MapQueryStringOperationEnricher::class),
            service(MapRequestPayloadOperationEnricher::class),
            service(MapUploadedFileOperationEnricher::class),
            service(PathVariablesOperationEnricher::class),
        ])
        ->arg('$useDefaultOperationEnrichers', false);

    // ***

    $services->set(OpenApiPathBuilderInterface::class, OpenApiPathBuilder::class);
    $services->set(RequestHandlerReflectorInterface::class, RequestHandlerReflector::class);
    $services->set(OpenApiDocumentManagerInterface::class, OpenApiDocumentManager::class);

    // ***

    $services->set(OpenApiBuildDocumentCommand::class);

    $services->set(OpenApiController::class)
        ->tag('controller.service_arguments');

    $services->set(SwaggerController::class)
        ->tag('controller.service_arguments');

    // **

    $services->set(JsonCodec::class);

    $services->set(CodecManagerInterface::class, CodecManager::class)
        ->arg('$codecs', [
            service(JsonCodec::class),
        ]);
};
