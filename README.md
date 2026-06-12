# OpenAPI Generator for Symfony Routing

![PHP](https://img.shields.io/packagist/dependency-v/sunrise-studio/symfony-openapi/php?style=social&logo=php&label=PHP)
![Coverage](https://img.shields.io/scrutinizer/coverage/g/sunrise-studio-development/symfony-openapi?style=social)
![Code quality](https://img.shields.io/scrutinizer/quality/g/sunrise-studio-development/symfony-openapi?style=social)

Languages: [English](README.md) | [Русский](README-ru.md) | [Українська](README-uk.md) | [Français](README-fr.md) | [Deutsch](README-de.md)

`sunrise-studio/symfony-openapi` generates an OpenAPI document from Symfony routes, controller signatures, Symfony HTTP kernel attributes, and PHP DTO/View classes.

Our goal is simple: application developers should not have to write large OpenAPI attribute blocks for normal API endpoints. We believe API documentation should follow the code that already describes the API: routes, input DTOs, query objects, uploaded files, path variables, and response view objects. Manual OpenAPI fragments should be used only for exceptional cases.

The package is built on the OpenAPI internals of [Sunrise HTTP Router](https://github.com/sunrise-php/http-router), but the Symfony-facing API lives in the `Sunrise\Symfony\OpenApi` namespace.

## Installation

```bash
composer require sunrise-studio/symfony-openapi
```

Register the bundle:

```php
// config/bundles.php
return [
    Symfony\Bundle\FrameworkBundle\FrameworkBundle::class => ['all' => true],
    Sunrise\Symfony\OpenApi\OpenApiBundle::class => ['all' => true],
];
```

Import the package routes:

```yaml
# config/routes.yaml
openapi:
  resource: '@OpenApiBundle/config/routes.php'
```

This imports two routes:

| Route | Controller | Purpose |
| --- | --- | --- |
| `GET /openapi` | `OpenApiController` | Serves the generated OpenAPI JSON document. |
| `GET /swagger.html` | `SwaggerController` | Serves Swagger UI configured to read `/openapi`. |

Both package routes are registered with `api: false`, so they are not included in the generated API document.

If you want only one route, import the route file directly:

```yaml
openapi_document:
  resource: '@OpenApiBundle/config/routes/openapi.php'

swagger_ui:
  resource: '@OpenApiBundle/config/routes/swagger.php'
```

Symfony references:

- [Routing](https://symfony.com/doc/current/routing.html)
- [Bundles](https://symfony.com/doc/current/bundles.html)

## Basic Configuration

A typical application configuration looks like this:

```yaml
# config/packages/openapi.yaml
parameters:
  openapi.initial_document:
    openapi: !php/const Sunrise\Http\Router\OpenApi\OpenApiConfiguration::VERSION
    info:
      title: API
      version: 1.0.0

  openapi.default_timestamp_format: !php/const DateTimeInterface::RFC3339_EXTENDED
```

Useful parameters:

| Parameter | Default | Purpose |
| --- | --- | --- |
| `openapi.initial_document` | OpenAPI version + `API` title | Base document merged with generated paths and schemas. |
| `openapi.initial_operation` | `responses: []` | Base operation used for every route. |
| `openapi.document_filename` | `%kernel.project_dir%/var/openapi.json` | Where the command writes the generated document. |
| `openapi.default_timestamp_format` | `OpenApiConfiguration::DEFAULT_TIMESTAMP_FORMAT` | PHP `date()` format used to generate OpenAPI `example` values for date/time schemas. |
| `openapi.default_empty_response_status` | `204` | Default status for `void` controller methods. |
| `openapi.default_response_status` | `200` | Default status for serialized return objects. |
| `openapi.default_response_formats` | `['json']` | Default Symfony response formats for serialized return objects. |

`SwaggerConfiguration` can also be replaced or configured as a service if you need custom Swagger UI assets, template variables, or a different OpenAPI URL.

## Building the Document

Run:

```bash
php bin/console openapi:build-document
```

The command reads the Symfony route collection, resolves route metadata, keeps only API routes, adapts them to the Sunrise OpenAPI document builder, and saves the document to `openapi.document_filename`.

After that:

- `/openapi` returns the generated JSON document.
- `/swagger.html` opens Swagger UI.

## Route Options

Route options are the default way to describe route-level metadata:

```php
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/pets', options: ['tags' => ['Pets']])]
final readonly class PetController
{
    #[Route('/{id}', methods: ['GET'], options: [
        'summary' => 'Finds pet by ID',
        'description' => 'Returns one pet.',
        'deprecated' => false,
        'response_status' => 200,
        'response_formats' => ['json'],
    ])]
    public function show(int $id): PetView
    {
        // ...
    }
}
```

Supported route options:

| Option | Type | Purpose |
| --- | --- | --- |
| `tags` | `string|string[]` | OpenAPI operation tags. |
| `summary` | `string` | OpenAPI operation summary. |
| `description` | `string` | OpenAPI operation description. |
| `deprecated`, `is_deprecated`, `isDeprecated` | `bool` | Marks an operation as deprecated. |
| `api`, `is_api`, `isApi` | `bool` | Includes or excludes the route from the generated document. |
| `response_status` | `int` | Overrides the generated response status. |
| `response_formats` | `string|string[]` | Symfony response formats, for example `json` or `xml`. |

If no API option is set, routes whose path starts with `/api/` are treated as API routes.

If you do not want to store tags, summaries, descriptions, or API filtering in route options, replace `RouteMetadataResolverInterface` with your own service.

## Symfony Attributes

The package supports Symfony controller value resolver attributes. See Symfony's [controller value resolver documentation](https://symfony.com/doc/current/controller/value_resolver.html).

### Request Body

`#[MapRequestPayload]` creates an OpenAPI `requestBody`.

```php
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;

public function create(#[MapRequestPayload(acceptFormat: 'json')] CreatePetRequest $request): PetView
{
    // ...
}
```

Behavior:

- The PHP type of the parameter becomes the request schema.
- `acceptFormat` is converted through Symfony's request format to a media type, for example `json` to `application/json`.
- If the parameter is required by PHP, the OpenAPI request body is marked as required.
- For array payloads, `MapRequestPayload(type: SomeDto::class)` describes the item type.

### Query Object

`#[MapQueryString]` describes a query object.

```php
use Symfony\Component\HttpKernel\Attribute\MapQueryString;

public function list(#[MapQueryString] PetSearchQuery $query): JsonResponse
{
    // ...
}
```

If `key` is `null`, the object is described as the whole query string with `style: form`. If `key` is set, the parameter uses `style: deepObject`.

### Query Parameter

`#[MapQueryParameter]` describes scalar, enum, date, UID, or array query parameters.

```php
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;

public function find(
    #[MapQueryParameter] PetStatus $status,
    #[MapQueryParameter] string ...$tags,
): JsonResponse {
    // ...
}
```

Variadic parameters are described as arrays and are not marked as required.

### Uploaded Files

`#[MapUploadedFile]` adds a `multipart/form-data` request body with binary fields.

```php
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpKernel\Attribute\MapUploadedFile;

public function upload(#[MapUploadedFile(name: 'photo')] UploadedFile $file): JsonResponse
{
    // ...
}
```

Variadic uploaded files are described as an array of binary strings and are not marked as required.

### Path Variables

Symfony path variables are read from compiled routes. Requirements are converted to OpenAPI schema patterns.

```php
#[Route('/api/pets/{petId}', requirements: ['petId' => '\d+'])]
public function show(int $petId): PetView
{
    // ...
}
```

Supported reflected parameter types for path variables:

- `bool`
- `int`
- `float`
- `string`
- `BackedEnum`
- `DateTimeInterface`
- `Symfony\Component\Uid\AbstractUid`

Symfony route mapping aliases are supported for simple mappings such as `['id' => 'petId']`. Entity-style mappings such as `{id:pet.id}` are intentionally not described as object schemas; the public path variable is still documented as a string unless a supported scalar parameter can be found.

### Date and Time

`#[MapDateTime(format: ...)]` changes the generated date/time example for controller parameters.

```php
use Symfony\Component\HttpKernel\Attribute\MapDateTime;

public function history(#[MapDateTime(format: 'Y-m-d')] DateTimeImmutable $date): JsonResponse
{
    // ...
}
```

## Response Generation

The default response behavior is intentionally small and predictable:

| Controller return type | Generated response |
| --- | --- |
| `void` | Empty response, default status `204`. |
| Symfony `Response` subclass | No automatic response content. Use `#[Operation]` for manual cases. |
| Any other named return type | Serialized response body, default status `200`, default format `json`. |

Example:

```php
#[Route('/api/pets/{id}', options: ['response_status' => 200])]
public function show(int $id): PetView
{
    // ...
}
```

If a route returns a custom view object, the return type is resolved through the PHP type schema resolver system and used as the response schema.

If your project wraps responses, for example `{data: ..., meta: ...}`, replace `ResponseMetadataResolverInterface` or the response operation enrichers with your own services.

## Manual OpenAPI Fragments

Most endpoints should not need manual OpenAPI fragments. For exceptional cases, use `#[Operation]`:

```php
use Sunrise\Http\Router\OpenApi\Type;
use Sunrise\Symfony\OpenApi\Annotation\Operation;

#[Operation([
    'responses' => [
        200 => [
            'description' => 'A list of pets.',
            'content' => [
                'application/json' => [
                    'schema' => [
                        'type' => 'array',
                        'items' => new Type(PetView::class),
                    ],
                ],
            ],
        ],
    ],
])]
public function list(): JsonResponse
{
    // ...
}
```

The fragment is merged into the generated operation by Sunrise `OpenApiDocumentManager`.

## Symfony OpenAPI Annotations

The package provides Symfony-facing annotations so application code does not need to import router or hydrator namespaces for common OpenAPI schema tasks:

| Annotation | Target | Purpose |
| --- | --- | --- |
| `#[Operation]` | class, method | Adds a manual OpenAPI operation fragment. |
| `#[ItemType]` | property, parameter | Describes array item type. |
| `#[SchemaName]` | class | Overrides component schema name. |
| `#[PropertyName]` | property | Overrides OpenAPI property name. |
| `#[IgnoreProperty]` | property | Excludes a property from object schema. |
| `#[TimestampFormat]` | property | Overrides date/time example format. |

## PHP Type Schema Resolution

The bundle registers the Sunrise schema resolvers explicitly and replaces the timestamp resolver with a Symfony-aware one.

Active resolvers:

- `BoolPhpTypeSchemaResolver`
- `IntPhpTypeSchemaResolver`
- `FloatPhpTypeSchemaResolver`
- `StringPhpTypeSchemaResolver`
- `ArrayPhpTypeSchemaResolver`
- `ArrayAccessPhpTypeSchemaResolver`
- `BackedEnumPhpTypeSchemaResolver`
- `ObjectPhpTypeSchemaResolver`
- `SymfonyUidPhpTypeSchemaResolver`
- `Sunrise\Symfony\OpenApi\PhpTypeSchemaResolver\TimestampPhpTypeSchemaResolver`

If your project has a custom type that needs a custom schema, implement `Sunrise\Http\Router\OpenApi\OpenApiPhpTypeSchemaResolverInterface` and register your resolver in the `OpenApiPhpTypeSchemaResolverManagerInterface` service.

## Object Schema Resolver

`Sunrise\Http\Router\OpenApi\PhpTypeSchemaResolver\ObjectPhpTypeSchemaResolver` is the main resolver for DTOs and view objects.

It reads PHP classes directly:

- instantiable non-internal classes are supported;
- public/private/protected properties are reflected;
- property types become OpenAPI property schemas;
- properties without a default value are marked as required;
- property default values are emitted when scalar or backed enum;
- constructor-promoted property defaults are supported;
- `#[SchemaName]` changes component schema name;
- `#[PropertyName]` changes property name;
- `#[IgnoreProperty]` excludes a property;
- `#[ItemType]` describes array properties;
- `#[TimestampFormat]` changes date/time examples.

This resolver does not use Symfony Serializer metadata. It does not read serializer groups, getters, setters, `SerializedName`, name converters, or camelCase/snake_case conversion rules.

We recommend explicit DTO and View classes with typed properties. If you need a different external shape, create a new View object and map your domain object into it. This keeps search, refactoring, and schema generation simple.

If your team needs first-class Symfony Serializer support, please open an issue. We will consider adding it as an optional resolver or strategy layer.

Symfony Serializer reference: [Serializer](https://symfony.com/doc/current/serializer.html).

## Extension Points

The package is intentionally built from small services:

| Service/interface | Purpose |
| --- | --- |
| `RouteMetadataResolverInterface` | Controls tags, summary, description, deprecation, and API filtering. |
| `ResponseMetadataResolverInterface` | Controls response status and response formats. |
| `OpenApiOperationEnricherInterface` | Adds request parameters, request bodies, responses, or custom operation data. |
| `OpenApiPhpTypeSchemaResolverInterface` | Converts PHP types to OpenAPI schemas. |
| `OpenApiPathBuilderInterface` | Converts Symfony route paths to OpenAPI paths. |

You can replace these services in your Symfony container when project policy differs from the defaults.

## Why This Package Exists

We have seen many APIs documented by long manual OpenAPI attribute blocks. That works, but it often makes documentation a second implementation of the same API.

We want the normal path to be different:

- routes describe paths and HTTP methods;
- Symfony attributes describe request mapping;
- DTOs describe input payloads;
- view objects describe output payloads;
- route options describe human operation metadata;
- OpenAPI-specific code is used only when the automatic model is not enough.

The closer the documentation is to real application code, the harder it is for the two to drift apart.
