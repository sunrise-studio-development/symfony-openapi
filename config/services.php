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
use Sunrise\Http\Router\OpenApi\PhpTypeSchemaResolver\TimestampPhpTypeSchemaResolver as SunriseTimestampResolver;
use Sunrise\Http\Router\OpenApi\SwaggerConfiguration;
use Sunrise\Http\Router\RequestHandlerReflectorInterface;
use Sunrise\Symfony\OpenApi\Command\BuildDocumentCommand;
use Sunrise\Symfony\OpenApi\Controller\DocumentController;
use Sunrise\Symfony\OpenApi\Controller\SwaggerController;
use Sunrise\Symfony\OpenApi\OpenApiPathBuilder;
use Sunrise\Symfony\OpenApi\OperationEnricher\MapQueryParameterOperationEnricher;
use Sunrise\Symfony\OpenApi\OperationEnricher\MapQueryStringOperationEnricher;
use Sunrise\Symfony\OpenApi\OperationEnricher\MapRequestPayloadOperationEnricher;
use Sunrise\Symfony\OpenApi\OperationEnricher\MapUploadedFileOperationEnricher;
use Sunrise\Symfony\OpenApi\OperationEnricher\PathVariablesOperationEnricher;
use Sunrise\Symfony\OpenApi\OperationEnricher\ResponseOperationEnricher;
use Sunrise\Symfony\OpenApi\PhpTypeSchemaResolver\TimestampPhpTypeSchemaResolver as SymfonyTimestampResolver;
use Sunrise\Symfony\OpenApi\RequestHandlerReflector;
use Sunrise\Symfony\OpenApi\ResponseMetadataResolver;
use Sunrise\Symfony\OpenApi\ResponseMetadataResolverInterface;
use Sunrise\Symfony\OpenApi\RouteMetadataResolver;
use Sunrise\Symfony\OpenApi\RouteMetadataResolverInterface;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Attribute\MapUploadedFile;

use function Symfony\Component\DependencyInjection\Loader\Configurator\service;
use function Symfony\Component\DependencyInjection\Loader\Configurator\tagged_iterator;

return static function (ContainerConfigurator $container): void {
    $parameters = $container->parameters();

    $services = $container->services();

    $services
        ->defaults()
        ->autowire()
        ->autoconfigure();

    // ***

    $parameters->set('openapi.initial_document', [
        'openapi' => '3.1.1',
        'info' => [
            'title' => 'API',
            'version' => '1.0.0',
        ],
    ]);

    $parameters->set('openapi.initial_operation', [
        'responses' => [
        ],
    ]);

    $parameters->set('openapi.document_filename', '%kernel.project_dir%/var/openapi.json');
    $parameters->set('openapi.document_uri', DocumentController::ROUTE_PATH);
    $parameters->set('openapi.default_timestamp_format', DateTimeInterface::RFC3339_EXTENDED);

    // ***

    $services->set(OpenApiConfiguration::class)
        ->arg('$initialDocument', '%openapi.initial_document%')
        ->arg('$initialOperation', '%openapi.initial_operation%')
        ->arg('$documentMediaType', MediaType::JSON)
        ->arg('$documentFilename', '%openapi.document_filename%')
        ->arg('$defaultTimestampFormat', '%openapi.default_timestamp_format%');

    $services->set(SwaggerConfiguration::class)
        ->arg('$openapiUri', '%openapi.document_uri%');

    // ***

    $services->set(ArrayAccessPhpTypeSchemaResolver::class)
        ->tag('openapi.php_type_schema_resolver');
    $services->set(ArrayPhpTypeSchemaResolver::class)
        ->tag('openapi.php_type_schema_resolver');
    $services->set(BackedEnumPhpTypeSchemaResolver::class)
        ->tag('openapi.php_type_schema_resolver');
    $services->set(BoolPhpTypeSchemaResolver::class)
        ->tag('openapi.php_type_schema_resolver');
    $services->set(FloatPhpTypeSchemaResolver::class)
        ->tag('openapi.php_type_schema_resolver');
    $services->set(IntPhpTypeSchemaResolver::class)
        ->tag('openapi.php_type_schema_resolver');
    $services->set(ObjectPhpTypeSchemaResolver::class)
        ->tag('openapi.php_type_schema_resolver');
    $services->set(StringPhpTypeSchemaResolver::class)
        ->tag('openapi.php_type_schema_resolver');
    $services->set(SunriseTimestampResolver::class);
    $services->set(SymfonyTimestampResolver::class)
        ->tag('openapi.php_type_schema_resolver');
    $services->set(SymfonyUidPhpTypeSchemaResolver::class)
        ->tag('openapi.php_type_schema_resolver');

    $services->set(OpenApiPhpTypeSchemaResolverManagerInterface::class, OpenApiPhpTypeSchemaResolverManager::class)
        ->arg('$phpTypeSchemaResolvers', tagged_iterator('openapi.php_type_schema_resolver'))
        ->arg('$useDefaultPhpTypeSchemaResolvers', false);

    // ***

    $services->set(MapQueryParameterOperationEnricher::class)
        ->tag('openapi.operation_enricher');
    $services->set(MapQueryStringOperationEnricher::class)
        ->tag('openapi.operation_enricher');
    $services->set(MapRequestPayloadOperationEnricher::class)
        ->tag('openapi.operation_enricher');
    $services->set(PathVariablesOperationEnricher::class)
        ->tag('openapi.operation_enricher');
    $services->set(ResponseOperationEnricher::class)
        ->tag('openapi.operation_enricher');

    // Symfony less than 7.1 doesn't contain this attribute.
    // https://github.com/symfony/symfony/blob/7.1/src/Symfony/Component/HttpKernel/CHANGELOG.md
    // https://symfony.com/blog/new-in-symfony-7-1-mapuploadedfile-attribute
    if (\class_exists(MapUploadedFile::class)) {
        $services->set(MapUploadedFileOperationEnricher::class)
            ->tag('openapi.operation_enricher');
    }

    $services->set(OpenApiOperationEnricherManagerInterface::class, OpenApiOperationEnricherManager::class)
        ->arg('$operationEnrichers', tagged_iterator('openapi.operation_enricher'))
        ->arg('$useDefaultOperationEnrichers', false);

    // ***

    $services->set(BuildDocumentCommand::class);

    $services->set(DocumentController::class)
        ->tag('controller.service_arguments');
    $services->set(SwaggerController::class)
        ->tag('controller.service_arguments');

    // ***

    $services->set(OpenApiDocumentManagerInterface::class, OpenApiDocumentManager::class);
    $services->set(OpenApiPathBuilderInterface::class, OpenApiPathBuilder::class);
    $services->set(RequestHandlerReflectorInterface::class, RequestHandlerReflector::class);
    $services->set(ResponseMetadataResolverInterface::class, ResponseMetadataResolver::class);
    $services->set(RouteMetadataResolverInterface::class, RouteMetadataResolver::class);

    // **

    $services->set(JsonCodec::class);

    $services->set(CodecManagerInterface::class, CodecManager::class)
        ->arg('$codecs', [
            service(JsonCodec::class),
        ]);
};
