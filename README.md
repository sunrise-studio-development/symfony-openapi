# OpenAPI Generator for Symfony Routing

[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/sunrise-studio-development/symfony-openapi/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/sunrise-studio-development/symfony-openapi/?branch=master)
[![Code Coverage](https://scrutinizer-ci.com/g/sunrise-studio-development/symfony-openapi/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/sunrise-studio-development/symfony-openapi/?branch=master)
[![Build Status](https://scrutinizer-ci.com/g/sunrise-studio-development/symfony-openapi/badges/build.png?b=master)](https://scrutinizer-ci.com/g/sunrise-studio-development/symfony-openapi/build-status/master)
[![Code Intelligence Status](https://scrutinizer-ci.com/g/sunrise-studio-development/symfony-openapi/badges/code-intelligence.svg?b=master)](https://scrutinizer-ci.com/code-intelligence)

Languages: [🇬🇧 English](README.md) | [🇨🇳 简体中文](README-zh-CN.md) | [🇪🇸 Español](README-es.md) | [🇵🇹 Português](README-pt.md) | [🇷🇺 Русский](README-ru.md) | [🇺🇦 Українська](README-uk.md)

This package generates an OpenAPI document from Symfony routes, controller signatures, Symfony HttpKernel attributes, and typed DTO/View classes.

The goal is to keep API documentation close to application code. Normal endpoints should not require large `#[OA\...]` blocks. Routes describe paths and methods, Symfony attributes describe request mapping, DTOs describe input data, view objects describe output data, and route options describe operation metadata. Manual OpenAPI fragments remain available for exceptional cases.

The public API lives in the `Sunrise\Symfony\OpenApi` namespace.

Further reading:

- [Article on Medium](https://medium.com/@a.fenric/openapi-without-oa-how-i-built-a-symfony-documentation-generator-af2ff83bd21c) explains what problem this package solves and how to use it in a Symfony application.
- [PHP Annotations plugin for PhpStorm](https://plugins.jetbrains.com/plugin/7320-php-annotations) is expected to support aliases for this package's attributes in upcoming releases, which should improve IDE completion and navigation.

## Installation

```bash
composer require sunrise-studio/symfony-openapi
```

The package requires PHP 8.2 or newer. Supported Symfony component versions are defined in `composer.json`. Symfony 8.1 or newer is only needed if your application wants to use Symfony's native `#[Serialize]` runtime attribute.

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

This imports two documentation routes:

| Route | Controller | Purpose |
| --- | --- | --- |
| `GET /docs` | `SwaggerController` | Serves Swagger UI configured to read `/docs/openapi.json`. |
| `GET /docs/openapi.json` | `DocumentController` | Serves the generated OpenAPI JSON document. |

These routes are not included in the generated API document because `api: true` is not set and their paths do not start with `/api/`.

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
| `openapi.document_uri` | `/docs/openapi.json` | Public URI of the generated document. Swagger UI uses it to load the document. |
| `openapi.default_timestamp_format` | `OpenApiConfiguration::DEFAULT_TIMESTAMP_FORMAT` | PHP `date()` format used to generate OpenAPI `example` values for date/time schemas. |

`SwaggerConfiguration` can be replaced as a service if you need custom Swagger UI assets or template variables.

### Custom Route Paths

If only Swagger UI needs a different path, define the route yourself:

```yaml
# config/routes.yaml
swagger_ui:
  path: /swagger.html
  controller: Sunrise\Symfony\OpenApi\Controller\SwaggerController
  methods: [GET]
  options:
    api: false
```

If the OpenAPI document route also changes, update both the route and `openapi.document_uri` so Swagger UI loads the correct document:

```yaml
# config/routes.yaml
openapi_document:
  path: /openapi.json
  controller: Sunrise\Symfony\OpenApi\Controller\DocumentController
  methods: [GET]
  options:
    api: false
```

```yaml
# config/packages/openapi.yaml
parameters:
  openapi.document_uri: /openapi.json
```

## Building The Document

Run:

```bash
php bin/console openapi:build-document
```

The command reads the route collection, keeps routes that should be documented, builds the OpenAPI document, and writes it to `openapi.document_filename`.

After generation, with the default package routes imported:

- `/docs` opens Swagger UI.
- `/docs/openapi.json` returns the generated JSON document.

## Route Options

Route options are the default place for operation metadata:

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
| `tag`, `tags` | `string\|string[]` | OpenAPI operation tags. |
| `summary` | `string` | OpenAPI operation summary. |
| `description` | `string` | OpenAPI operation description. |
| `deprecated`, `is_deprecated`, `isDeprecated` | `bool` | Marks an operation as deprecated. |
| `api`, `is_api`, `isApi` | `bool` | Includes or excludes the route from the generated document. |
| `response_code` | `int` | Documented response status when `#[Serialize]` does not provide one. Defaults are `200` for a response body and `204` for explicit `void`. |
| `response_format` | `string` | Response format for the documented response body, converted to a media type, for example `json` to `application/json`. |
| `response_formats` | `string[]` | Multiple response formats. Ignored when `response_format` is set. |

If no API option is set, routes whose path starts with `/api/` are treated as API routes.

If your project does not want to keep tags, summaries, descriptions, and API filtering in route options, replace `RouteMetadataResolverInterface`.

## Symfony Attributes

The package understands the Symfony controller attributes that describe request data. See Symfony's [controller value resolver documentation](https://symfony.com/doc/current/controller/value_resolver.html).

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

Without `key`, the parameter name is the PHP parameter name and the object uses `style: form`. With `key`, that key becomes the parameter name and the object uses `style: deepObject`.

> [!NOTE]
> Symfony versions earlier than 7.3 do not contain the `MapQueryString::key` argument. See the [Symfony 7.3 HttpKernel changelog](https://github.com/symfony/symfony/blob/7.3/src/Symfony/Component/HttpKernel/CHANGELOG.md) and [Symfony 7.3 DX improvements](https://symfony.com/blog/new-in-symfony-7-3-dx-improvements-part-2#improved-mapquerystring).

### Request Body

`#[MapRequestPayload]` creates an OpenAPI `requestBody`.

```php
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;

public function create(#[MapRequestPayload(acceptFormat: 'json')] CreatePetRequest $request): PetView
{
    // ...
}
```

Generated request body:

- The PHP parameter type becomes the request schema.
- `acceptFormat` is optional. If omitted, the route `_format` default is used; if `_format` is also missing, `json` is used.
- `acceptFormat` is converted from Symfony request format to media type, for example `json` to `application/json`.
- If the PHP parameter is required, the OpenAPI request body is required.
- For array payloads, `MapRequestPayload(type: SomeDto::class)` describes the item type.

> [!NOTE]
> Symfony versions earlier than 7.1 do not contain the `MapRequestPayload::type` argument. See the [Symfony 7.1 HttpKernel changelog](https://github.com/symfony/symfony/blob/7.1/src/Symfony/Component/HttpKernel/CHANGELOG.md).

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

> [!NOTE]
> Symfony versions earlier than 7.1 do not contain the `MapUploadedFile` attribute. See the [Symfony 7.1 HttpKernel changelog](https://github.com/symfony/symfony/blob/7.1/src/Symfony/Component/HttpKernel/CHANGELOG.md) and [Symfony 7.1 MapUploadedFile announcement](https://symfony.com/blog/new-in-symfony-7-1-mapuploadedfile-attribute).

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

Write controller return types as the public API should look.

| Controller return type | Generated response |
| --- | --- |
| View object, DTO, scalar, array | JSON response body. The schema is read from the method return type. Status is `200` by default. |
| Explicit `void` | Empty response. Status is `204` by default. |
| Symfony `Response` subclass | Response body is not generated automatically. Use `#[Operation]` when it must be documented manually. |

For JSON APIs, no response format option is needed. Use route options only when the defaults do not match the endpoint:

- `response_code` changes the documented status, for example `201` for create actions.
- `response_format` documents one non-default response format.
- `response_formats` documents multiple response formats.

```php
#[Route('/api/pets/{id}', methods: ['GET'])]
public function show(int $id): PetView
{
    // ...
}
```

Symfony 8.1 introduced [`#[Serialize]`](https://symfony.com/blog/new-in-symfony-8-1-serialize-attribute), which serializes controller results at runtime. When that attribute is present, this package reads `Serialize::code`; the schema still comes from the PHP return type.

```php
use Symfony\Component\HttpKernel\Attribute\Serialize;

#[Route('/api/pets', methods: ['POST'], options: ['response_code' => 201])]
#[Serialize(code: 201)]
public function create(CreatePetRequest $request): PetView
{
    // ...
}
```

Use an explicit `void` return type for actions with no response body:

```php
#[Route('/api/pets/{id}', methods: ['DELETE'])]
public function delete(int $id): void
{
    // ...
}
```

This documents the endpoint as an empty `204` response. Symfony itself does not convert a `null` controller result to `204`, so applications using `void` actions should handle that at runtime.

If the application cannot use Symfony 8.1 yet, a small `KernelEvents::VIEW` listener can handle both cases: `null` becomes `204`, and other controller results are serialized as JSON. Symfony's own implementation is [`SerializeControllerResultAttributeListener`](https://github.com/symfony/http-kernel/blob/ad1426284c2e7fe10de65dc68a25a724639e3838/EventListener/SerializeControllerResultAttributeListener.php); a minimal JSON-only listener can be this simple:

```php
namespace App\EventListener;

use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ViewEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Serializer\SerializerInterface;

#[AsEventListener(event: KernelEvents::VIEW)]
final readonly class JsonControllerResultListener
{
    public function __construct(
        private SerializerInterface $serializer,
    ) {
    }

    public function __invoke(ViewEvent $event): void
    {
        $result = $event->getControllerResult();
        if ($result === null) {
            $event->setResponse(new Response(status: 204));
            return;
        }

        $event->setResponse(new JsonResponse(
            $this->serializer->serialize($result, 'json'),
            200,
            json: true,
        ));
    }
}
```

If your project has different rules for response status or formats, replace `ResponseMetadataResolverInterface`.

## OpenAPI Attributes

The package provides small OpenAPI attributes for cases where PHP types are not enough:

| Attribute | Target | Purpose |
| --- | --- | --- |
| `#[Operation]` | class, method | Adds a manual OpenAPI operation fragment. |
| `#[ItemType]` | property, parameter | Describes array item type. |
| `#[SchemaName]` | class | Overrides component schema name. |
| `#[PropertyName]` | property | Overrides OpenAPI property name. |
| `#[IgnoreProperty]` | property | Excludes a property from object schema. |
| `#[TimestampFormat]` | property | Overrides date/time example format. |

Array item types are usually read from PHPDoc:

```php
/** @var list<PetView> */
public array $pets;
```

Supported PHPDoc forms include `PetView[]`, `list<PetView>`, `array<PetView>`, and `array<string, PetView>`. Nullable item types such as `array<PetView|null>` are supported. Broad or ambiguous item types such as `array<mixed>` and `array<PetView|ErrorView>` are ignored. Use `#[ItemType]` when you need an explicit override or an item limit; it has priority over `@var`.

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

The default schema generation covers common PHP types:

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

If your project has a custom PHP type that needs a custom schema, implement `OpenApiPhpTypeSchemaResolverInterface`. With autoconfigure enabled, the resolver will be registered automatically. Otherwise, tag it with `openapi.php_type_schema_resolver`.

## Object Schemas

DTO and View objects are described from typed properties.

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
- array item types are read from `@var` when possible;
- `#[ItemType]` explicitly describes array properties and has priority over `@var`;
- `#[TimestampFormat]` changes date/time examples.

This resolver does not use Symfony Serializer metadata. It does not read serializer groups, getters, setters, `SerializedName`, name converters, or camelCase/snake_case conversion rules.

We recommend explicit DTO and View classes with typed properties. If you need a different external shape, create a new View object and map your domain object into it. This keeps search, refactoring, and schema generation simple.

If your team needs first-class Symfony Serializer support, open an issue. We will consider adding it as an optional resolver or strategy.

Symfony Serializer reference: [Serializer](https://symfony.com/doc/current/serializer.html).

## Extension Points

The package is built from replaceable services for projects that need different conventions:

| Service/interface | Purpose | Tag |
| --- | --- | --- |
| `RouteMetadataResolverInterface` | Controls tags, summary, description, deprecation, and API filtering. | — |
| `ResponseMetadataResolverInterface` | Controls response status codes and response formats. | — |
| `OpenApiOperationEnricherInterface` | Adds request parameters, request bodies, responses, or custom operation data. | `openapi.operation_enricher` (added automatically with autoconfigure) |
| `OpenApiPhpTypeSchemaResolverInterface` | Converts PHP types to OpenAPI schemas. | `openapi.php_type_schema_resolver` (added automatically with autoconfigure) |
| `OpenApiPathBuilderInterface` | Converts Symfony route paths to OpenAPI paths. | — |

Replace these services in the Symfony container when project rules differ from the defaults.
