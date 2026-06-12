# OpenAPI Generator for Symfony Routing

![PHP](https://img.shields.io/packagist/dependency-v/sunrise-studio/symfony-openapi/php?style=social&logo=php&label=PHP)
![Coverage](https://img.shields.io/scrutinizer/coverage/g/sunrise-studio-development/symfony-openapi?style=social)
![Code quality](https://img.shields.io/scrutinizer/quality/g/sunrise-studio-development/symfony-openapi?style=social)

Idiomas: [🇬🇧 English](README.md) | [🇨🇳 简体中文](README-zh-CN.md) | [🇪🇸 Español](README-es.md) | [🇵🇹 Português](README-pt.md) | [🇷🇺 Русский](README-ru.md) | [🇺🇦 Українська](README-uk.md)

Este paquete genera un documento OpenAPI a partir de rutas Symfony, firmas de controladores, atributos Symfony HttpKernel y clases DTO/View tipadas.

El objetivo es mantener la documentación de la API cerca del código de la aplicación. Los endpoints normales no deberían necesitar grandes bloques `#[OA\...]`. Las rutas describen paths y methods, los atributos Symfony describen request mapping, los DTO describen datos de entrada, los view objects describen datos de salida y las route options describen operation metadata. Los fragmentos OpenAPI manuales quedan disponibles para casos excepcionales.

La API vive en el namespace `Sunrise\Symfony\OpenApi`. Internamente, el paquete usa el OpenAPI engine de [Sunrise HTTP Router](https://github.com/sunrise-php/http-router).

## Instalación

```bash
composer require sunrise-studio/symfony-openapi
```

Registra el bundle:

```php
// config/bundles.php
return [
    Symfony\Bundle\FrameworkBundle\FrameworkBundle::class => ['all' => true],
    Sunrise\Symfony\OpenApi\OpenApiBundle::class => ['all' => true],
];
```

Importa las rutas del paquete:

```yaml
# config/routes.yaml
openapi:
  resource: '@OpenApiBundle/config/routes.php'
```

Esto importa dos rutas:

| Route | Controller | Propósito |
| --- | --- | --- |
| `GET /openapi` | `OpenApiController` | Sirve el documento OpenAPI JSON generado. |
| `GET /swagger.html` | `SwaggerController` | Sirve Swagger UI configurado para leer `/openapi`. |

Estas rutas no se incluyen en el API document generado: no tienen `api: true` y sus paths no empiezan con `/api/`.

Si solo necesitas una ruta, importa su archivo directamente:

```yaml
openapi_document:
  resource: '@OpenApiBundle/config/routes/openapi.php'

swagger_ui:
  resource: '@OpenApiBundle/config/routes/swagger.php'
```

Referencias Symfony:

- [Routing](https://symfony.com/doc/current/routing.html)
- [Bundles](https://symfony.com/doc/current/bundles.html)

## Configuración

Configuración típica de una aplicación:

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

Parámetros útiles:

| Parámetro | Default | Propósito |
| --- | --- | --- |
| `openapi.initial_document` | OpenAPI version + `API` title | Documento base que se fusiona con los generated paths y schemas. |
| `openapi.initial_operation` | `responses: []` | Operation base que se fusiona con cada generated operation. |
| `openapi.document_filename` | `%kernel.project_dir%/var/openapi.json` | Output file usado por `openapi:build-document`. |
| `openapi.default_timestamp_format` | `OpenApiConfiguration::DEFAULT_TIMESTAMP_FORMAT` | Formato PHP `date()` usado para generar valores OpenAPI `example` para schemas de fecha/hora. |
| `openapi.default_empty_response_status` | `204` | Status por defecto para controller methods con `void`. |
| `openapi.default_response_status` | `200` | Status por defecto para return objects serializables. |
| `openapi.default_response_formats` | `['json']` | Symfony response formats por defecto para return objects serializables. |

`SwaggerConfiguration` puede reemplazarse como servicio si necesitas Swagger UI assets, template variables o una OpenAPI URL diferente.

## Construir El Documento

Ejecuta:

```bash
php bin/console openapi:build-document
```

El comando lee la colección de rutas, resuelve route metadata, conserva API routes, construye el OpenAPI document y lo escribe en `openapi.document_filename`.

Después de generar:

- `/openapi` devuelve el JSON document generado.
- `/swagger.html` abre Swagger UI.

## Route Options

Route options son la fuente por defecto para route-level OpenAPI metadata:

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

Options soportadas:

| Option | Type | Propósito |
| --- | --- | --- |
| `tags` | `string\|string[]` | OpenAPI operation tags. |
| `summary` | `string` | OpenAPI operation summary. |
| `description` | `string` | OpenAPI operation description. |
| `deprecated`, `is_deprecated`, `isDeprecated` | `bool` | Marca una operation como deprecated. |
| `api`, `is_api`, `isApi` | `bool` | Incluye o excluye la route del generated document. |
| `response_status` | `int` | Sobrescribe el generated response status. |
| `response_formats` | `string\|string[]` | Symfony response formats, por ejemplo `json` o `xml`. |

Si no se define ninguna API option, las routes cuyo path empieza con `/api/` se tratan como API routes.

Si route options no son el lugar correcto para la metadata de tu proyecto, reemplaza `RouteMetadataResolverInterface`.

## Symfony Attributes

El paquete soporta Symfony controller value resolver attributes. Consulta la [documentación de Symfony](https://symfony.com/doc/current/controller/value_resolver.html).

### Path Variables

Symfony path variables se leen desde las rutas compiladas. Requirements se convierten en OpenAPI schema patterns.

```php
#[Route('/api/pets/{petId}', requirements: ['petId' => '\d+'])]
public function show(int $petId): PetView
{
    // ...
}
```

Reflected parameter types soportados para path variables:

- `bool`
- `int`
- `float`
- `string`
- `BackedEnum`
- `DateTimeInterface`
- `Symfony\Component\Uid\AbstractUid`

Symfony route mapping aliases se soportan para mappings simples como `['id' => 'petId']`. Entity-style mappings como `{id:pet.id}` no se describen como object schemas; la path variable pública se documenta como string si no se encuentra un scalar parameter soportado.

### Query Parameter

`#[MapQueryParameter]` describe scalar, enum, date/time, UID o array query parameters.

```php
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;

public function find(
    #[MapQueryParameter] PetStatus $status,
    #[MapQueryParameter] string ...$tags,
): JsonResponse {
    // ...
}
```

Los variadic parameters se describen como arrays y no se marcan como required.

### Query Object

`#[MapQueryString]` describe un query object.

```php
use Symfony\Component\HttpKernel\Attribute\MapQueryString;

public function list(#[MapQueryString] PetSearchQuery $query): JsonResponse
{
    // ...
}
```

Si `key` es `null`, el objeto se describe como todo el query string con `style: form`. Si `key` está definido, el parámetro usa `style: deepObject`.

### Request Body

`#[MapRequestPayload]` crea un OpenAPI `requestBody`.

```php
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;

public function create(#[MapRequestPayload(acceptFormat: 'json')] CreatePetRequest $request): PetView
{
    // ...
}
```

Comportamiento:

- El PHP parameter type se convierte en request schema.
- `acceptFormat` es opcional. Si se omite, se usan los default accept formats; por defecto es `json`.
- `acceptFormat` se convierte desde Symfony request format a media type, por ejemplo `json` a `application/json`.
- Si el PHP parameter es required, el OpenAPI request body también es required.
- Para array payloads, `MapRequestPayload(type: SomeDto::class)` describe el item type.

### Uploaded Files

`#[MapUploadedFile]` añade un `multipart/form-data` request body con binary fields.

```php
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpKernel\Attribute\MapUploadedFile;

public function upload(#[MapUploadedFile(name: 'photo')] UploadedFile $file): JsonResponse
{
    // ...
}
```

Los variadic uploaded files se describen como un array de binary strings y no se marcan como required.

### Date And Time

`#[MapDateTime(format: ...)]` cambia el generated date/time example para controller parameters.

```php
use Symfony\Component\HttpKernel\Attribute\MapDateTime;

public function history(#[MapDateTime(format: 'Y-m-d')] DateTimeImmutable $date): JsonResponse
{
    // ...
}
```

El argumento `format` es opcional. Si se omite, se usa el default timestamp format.

## Generación De Respuestas

La generación de responses por defecto es intencionalmente limitada:

| Return type del controlador | Generated response |
| --- | --- |
| `void` | Empty response, default status `204`. |
| Symfony `Response` subclass | No se genera automatic response content. Usa `#[Operation]` cuando la response deba documentarse manualmente. |
| Any other named return type | Serialized response body, default status `200`, default format `json`. |

Ejemplo:

```php
#[Route('/api/pets/{id}', options: ['response_status' => 200])]
public function show(int $id): PetView
{
    // ...
}
```

Si una route devuelve un custom view object, el return type se usa como response schema.

Si tu proyecto envuelve responses, por ejemplo `{data: ..., meta: ...}`, reemplaza `ResponseMetadataResolverInterface` o los response operation enrichers.

## OpenAPI Attributes

El paquete proporciona OpenAPI attributes para tareas comunes de schema:

| Attribute | Target | Propósito |
| --- | --- | --- |
| `#[Operation]` | class, method | Añade un manual OpenAPI operation fragment. |
| `#[ItemType]` | property, parameter | Describe array item type. |
| `#[SchemaName]` | class | Sobrescribe component schema name. |
| `#[PropertyName]` | property | Sobrescribe OpenAPI property name. |
| `#[IgnoreProperty]` | property | Excluye una property del object schema. |
| `#[TimestampFormat]` | property | Sobrescribe date/time example format. |

## Fragmentos OpenAPI Manuales

La mayoría de endpoints no deberían necesitar OpenAPI fragments manuales. Para casos excepcionales, usa `#[Operation]`:

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

El fragment se fusiona con la generated operation.

## PHP Type Schema Resolvers

Resolvers registrados:

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

Si tu proyecto tiene un PHP type custom que necesita un custom schema, implementa `OpenApiPhpTypeSchemaResolverInterface` y registra el resolver en el servicio `OpenApiPhpTypeSchemaResolverManagerInterface`.

## Object Schema Resolver

`ObjectPhpTypeSchemaResolver` es el resolver principal para DTOs y view objects.

Lee PHP classes directamente:

- se soportan instantiable non-internal classes;
- se reflejan public, protected y private properties;
- property types se convierten en OpenAPI property schemas;
- properties sin default value se marcan como required;
- scalar y backed enum default values se emiten en el schema;
- se soportan constructor-promoted property defaults;
- `#[SchemaName]` cambia component schema name;
- `#[PropertyName]` cambia property name;
- `#[IgnoreProperty]` excluye una property;
- `#[ItemType]` describe array properties;
- `#[TimestampFormat]` cambia date/time examples.

Este resolver no usa Symfony Serializer metadata. No lee serializer groups, getters, setters, `SerializedName`, name converters ni reglas de conversión camelCase/snake_case.

Recomendamos DTO y View classes explícitas con typed properties. Si necesitas otra forma externa, crea un nuevo View object y mapea tu domain object a él. Esto mantiene simples la búsqueda, el refactoring y la schema generation.

Si tu equipo necesita first-class Symfony Serializer support, abre un issue. Consideraremos añadirlo como optional resolver o strategy.

Referencia Symfony Serializer: [Serializer](https://symfony.com/doc/current/serializer.html).

## Puntos De Extensión

El paquete está formado por servicios reemplazables:

| Service/interface | Propósito |
| --- | --- |
| `RouteMetadataResolverInterface` | Controla tags, summary, description, deprecation y API filtering. |
| `ResponseMetadataResolverInterface` | Controla response status y response formats. |
| `OpenApiOperationEnricherInterface` | Añade request parameters, request bodies, responses o custom operation data. |
| `OpenApiPhpTypeSchemaResolverInterface` | Convierte PHP types en OpenAPI schemas. |
| `OpenApiPathBuilderInterface` | Convierte Symfony route paths en OpenAPI paths. |

Reemplaza estos servicios en el Symfony container cuando las reglas del proyecto difieran de los defaults.
