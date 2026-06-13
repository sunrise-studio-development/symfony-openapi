# OpenAPI Generator for Symfony Routing

[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/sunrise-studio-development/symfony-openapi/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/sunrise-studio-development/symfony-openapi/?branch=master)
[![Code Coverage](https://scrutinizer-ci.com/g/sunrise-studio-development/symfony-openapi/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/sunrise-studio-development/symfony-openapi/?branch=master)
[![Build Status](https://scrutinizer-ci.com/g/sunrise-studio-development/symfony-openapi/badges/build.png?b=master)](https://scrutinizer-ci.com/g/sunrise-studio-development/symfony-openapi/build-status/master)
[![Code Intelligence Status](https://scrutinizer-ci.com/g/sunrise-studio-development/symfony-openapi/badges/code-intelligence.svg?b=master)](https://scrutinizer-ci.com/code-intelligence)

Languages: [🇬🇧 English](README.md) | [🇨🇳 简体中文](README-zh-CN.md) | [🇪🇸 Español](README-es.md) | [🇵🇹 Português](README-pt.md) | [🇷🇺 Русский](README-ru.md) | [🇺🇦 Українська](README-uk.md)

This package generates an OpenAPI document from Symfony routes, controller signatures, Symfony HttpKernel attributes, and typed DTO/View classes.

The goal is to keep API documentation close to application code. Normal endpoints should not require large `#[OA\...]` blocks. Routes describe paths and methods, Symfony attributes describe request mapping, DTOs describe input data, view objects describe output data, and route options describe operation metadata. Manual OpenAPI fragments remain available for exceptional cases.

The API lives in the `Sunrise\Symfony\OpenApi` namespace. The package uses the OpenAPI engine from [Sunrise HTTP Router](https://github.com/sunrise-php/http-router) internally.

## Installation

```bash
composer require sunrise-studio/symfony-openapi
```

The package requires Symfony HttpKernel 8.1 or newer.

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
| `GET /openapi` | `DocumentController` | Serves the generated OpenAPI JSON document. |
| `GET /swagger.html` | `SwaggerController` | Serves Swagger UI configured to read `/openapi`. |

These routes are not included in the generated API document: `api: true` is not set, and their paths do not start with `/api/`.

If you need only one route, import its file directly:

```yaml
openapi_document:
  resource: '@OpenApiBundle/config/routes/document.php'

swagger_ui:
  resource: '@OpenApiBundle/config/routes/swagger.php'
```

Symfony references:

- [Routing](https://symfony.com/doc/current/routing.html)
- [Bundles](https://symfony.com/doc/current/bundles.html)

## Configuration

Typical application configuration:

```yaml
# config/packages/openapi.yaml
parameters:
  openapi.initial_document:
    openapi: 3.1.1
    info:
      title: API
      version: 1.0.0

  openapi.default_timestamp_format: !php/const DateTimeInterface::RFC3339_EXTENDED
```

Useful parameters:

| Parameter | Default | Purpose |
| --- | --- | --- |
| `openapi.initial_document` | OpenAPI version + `API` title | Base document merged with generated paths and schemas. |
| `openapi.initial_operation` | `responses: []` | Base operation merged into every generated operation. |
| `openapi.document_filename` | `%kernel.project_dir%/var/openapi.json` | Output file used by `openapi:build-document`. |
| `openapi.document_uri` | `/openapi` | Public URI of the generated document. Swagger UI uses it to load the document. |
| `openapi.default_timestamp_format` | `OpenApiConfiguration::DEFAULT_TIMESTAMP_FORMAT` | PHP `date()` format used to generate OpenAPI `example` values for date/time schemas. |

`SwaggerConfiguration` can be replaced as a service if you need custom Swagger UI assets or template variables.

### Custom Route Paths

If only Swagger UI needs a different path, define the route yourself:

```yaml
# config/routes.yaml
swagger_ui:
  path: /docs
  controller: Sunrise\Symfony\OpenApi\Controller\SwaggerController
  methods: [GET]
  options:
    api: false
```

If the OpenAPI document route also changes, update both the route and `openapi.document_uri` so Swagger UI loads the correct document:

```yaml
# config/routes.yaml
openapi_document:
  path: /docs/openapi.json
  controller: Sunrise\Symfony\OpenApi\Controller\DocumentController
  methods: [GET]
  options:
    api: false
```

```yaml
# config/packages/openapi.yaml
parameters:
  openapi.document_uri: /docs/openapi.json
```

## Building The Document

Run:

```bash
php bin/console openapi:build-document
```

The command reads the route collection, resolves route metadata, keeps API routes, builds the OpenAPI document, and writes it to `openapi.document_filename`.

After generation:

- `/openapi` returns the generated JSON document.
- `/swagger.html` opens Swagger UI.

## Route Options

Route options are the default source for route-level OpenAPI metadata:

```php
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/pets', options: ['tags' => ['Pets']])]
final readonly class PetController
{
    #[Route('/{id}', methods: ['GET'], options: [
        'summary' => 'Finds pet by ID',
        'description' => 'Returns one pet.',
        'deprecated' => false,
    ])]
    public function show(int $id): PetView
    {
        // ...
    }
}
```

Supported options:

| Option | Type | Purpose |
| --- | --- | --- |
| `tags` | `string\|string[]` | OpenAPI operation tags. |
| `summary` | `string` | OpenAPI operation summary. |
| `description` | `string` | OpenAPI operation description. |
| `deprecated`, `is_deprecated`, `isDeprecated` | `bool` | Marks an operation as deprecated. |
| `api`, `is_api`, `isApi` | `bool` | Includes or excludes the route from the generated document. |

If no API option is set, routes whose path starts with `/api/` are treated as API routes.

If route options are not the right place for metadata in your project, replace `RouteMetadataResolverInterface`.

## Symfony Attributes

The package supports Symfony controller value resolver attributes. See Symfony's [controller value resolver documentation](https://symfony.com/doc/current/controller/value_resolver.html).

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

Symfony route mapping aliases are supported for simple mappings such as `['id' => 'petId']`. Entity-style mappings such as `{id:pet.id}` are not described as object schemas; the public path variable is documented as a string unless a supported scalar parameter can be found.

### Query Parameter

`#[MapQueryParameter]` describes scalar, enum, date/time, UID, or array query parameters.

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

- The PHP parameter type becomes the request schema.
- `acceptFormat` is optional. If omitted, default accept formats are used; by default this is `json`.
- `acceptFormat` is converted from Symfony request format to media type, for example `json` to `application/json`.
- If the PHP parameter is required, the OpenAPI request body is required.
- For array payloads, `MapRequestPayload(type: SomeDto::class)` describes the item type.

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

### Date And Time

`#[MapDateTime(format: ...)]` changes the generated date/time example for controller parameters.

```php
use Symfony\Component\HttpKernel\Attribute\MapDateTime;

public function history(#[MapDateTime(format: 'Y-m-d')] DateTimeImmutable $date): JsonResponse
{
    // ...
}
```

The `format` argument is optional. If omitted, the default timestamp format is used.

## Responses

The package documents responses only when the controller explicitly describes how the result is returned.

| Controller metadata | Generated response |
| --- | --- |
| `#[Serialize]` | Serialized response body. Status is read from `Serialize::code`; the schema is read from the method return type. |
| `#[EmptyResponse]` | Empty response. Default status is `204`. |
| Symfony `Response` subclass without OpenAPI attributes | No automatic response content. Use `#[Operation]` or `#[EmptyResponse]` when the response must be documented. |

Serialized responses use the route `_format` default as the Symfony response format. If `_format` is not set, `json` is used. The format is converted to a media type with `Request::getMimeTypes()`.

```php
use Symfony\Component\HttpKernel\Attribute\Serialize;

#[Route('/api/pets/{id}', format: 'json')]
#[Serialize(code: 200)]
public function show(int $id): PetView
{
    // ...
}
```

If a route returns a custom view object, `#[Serialize]` makes the return type the response schema.

Use `#[EmptyResponse]` for actions with no response body:

```php
use Sunrise\Symfony\OpenApi\Annotation\EmptyResponse;

#[Route('/api/pets/{id}', methods: ['DELETE'])]
#[EmptyResponse]
public function delete(int $id): void
{
    // ...
}
```

For wrappers such as `{data: ..., meta: ...}`, add a custom operation enricher or describe the response with `#[Operation]`.

## OpenAPI Attributes

The package provides OpenAPI attributes for common schema tasks:

| Attribute | Target | Purpose |
| --- | --- | --- |
| `#[Operation]` | class, method | Adds a manual OpenAPI operation fragment. |
| `#[EmptyResponse]` | class, method | Adds an empty OpenAPI response, `204` by default. |
| `#[ItemType]` | property, parameter | Describes array item type. |
| `#[SchemaName]` | class | Overrides component schema name. |
| `#[PropertyName]` | property | Overrides OpenAPI property name. |
| `#[IgnoreProperty]` | property | Excludes a property from object schema. |
| `#[TimestampFormat]` | property | Overrides date/time example format. |

## Manual OpenAPI Fragments

Most endpoints should not need manual OpenAPI fragments. For exceptional cases, use `#[Operation]`:

```php
use Sunrise\Symfony\OpenApi\Annotation\Operation;
use Sunrise\Symfony\OpenApi\Type;

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

The fragment is merged into the generated operation.

### Documenting Errors

We recommend keeping API actions predictable: a successful action should return one documented view object, and errors should use a documented error shape instead of being hidden in controller branches.

For a shared error response, describe a `default` response in `#[Operation]` or in `openapi.initial_operation`:

```yaml
# config/packages/openapi.yaml
parameters:
  openapi.initial_operation:
    responses:
      default:
        description: The operation was unsuccessful.
        content:
          application/json:
            schema: App\View\ErrorView
```

```php
use App\View\ErrorView;
use Sunrise\Symfony\OpenApi\Annotation\Operation;
use Sunrise\Symfony\OpenApi\Type;

#[Operation([
    'responses' => [
        'default' => [
            'description' => 'The operation was unsuccessful.',
            'content' => [
                'application/json' => [
                    'schema' => new Type(ErrorView::class),
                ],
            ],
        ],
    ],
])]
final readonly class PetController
{
}
```

In YAML/PHP arrays, a schema value may be a PHP type string. It is treated as a PHP type when the string contains `\`. For a class without a namespace, use a leading backslash, for example `\AppErrorView`. In PHP attributes, use `new Type(ErrorView::class)` when you need an explicit type object.

## PHP Type Schema Resolvers

Registered resolvers:

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

If your project has a custom PHP type that needs a custom schema, implement `OpenApiPhpTypeSchemaResolverInterface` and register the resolver in the `OpenApiPhpTypeSchemaResolverManagerInterface` service.

## Object Schema Resolver

`ObjectPhpTypeSchemaResolver` is the main resolver for DTOs and view objects.

It reads PHP classes directly:

- instantiable non-internal classes are supported;
- public, protected, and private properties are reflected;
- property types become OpenAPI property schemas;
- properties without a default value are marked as required;
- scalar and backed enum default values are emitted;
- constructor-promoted property defaults are supported;
- `#[SchemaName]` changes component schema name;
- `#[PropertyName]` changes property name;
- `#[IgnoreProperty]` excludes a property;
- `#[ItemType]` describes array properties;
- `#[TimestampFormat]` changes date/time examples.

This resolver does not use Symfony Serializer metadata. It does not read serializer groups, getters, setters, `SerializedName`, name converters, or camelCase/snake_case conversion rules.

We recommend explicit DTO and View classes with typed properties. If you need a different external shape, create a new View object and map your domain object into it. This keeps search, refactoring, and schema generation simple.

If your team needs first-class Symfony Serializer support, open an issue. We will consider adding it as an optional resolver or strategy.

Symfony Serializer reference: [Serializer](https://symfony.com/doc/current/serializer.html).

## Extension Points

The package is built from replaceable services:

| Service/interface | Purpose |
| --- | --- |
| `RouteMetadataResolverInterface` | Controls tags, summary, description, deprecation, and API filtering. |
| `OpenApiOperationEnricherInterface` | Adds request parameters, request bodies, responses, or custom operation data. |
| `OpenApiPhpTypeSchemaResolverInterface` | Converts PHP types to OpenAPI schemas. |
| `OpenApiPathBuilderInterface` | Converts Symfony route paths to OpenAPI paths. |

Replace these services in the Symfony container when project rules differ from the defaults.
