# OpenAPI Generator for Symfony Routing

[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/sunrise-studio-development/symfony-openapi/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/sunrise-studio-development/symfony-openapi/?branch=master)
[![Code Coverage](https://scrutinizer-ci.com/g/sunrise-studio-development/symfony-openapi/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/sunrise-studio-development/symfony-openapi/?branch=master)
[![Build Status](https://scrutinizer-ci.com/g/sunrise-studio-development/symfony-openapi/badges/build.png?b=master)](https://scrutinizer-ci.com/g/sunrise-studio-development/symfony-openapi/build-status/master)
[![Code Intelligence Status](https://scrutinizer-ci.com/g/sunrise-studio-development/symfony-openapi/badges/code-intelligence.svg?b=master)](https://scrutinizer-ci.com/code-intelligence)

Idiomas: [🇬🇧 English](README.md) | [🇨🇳 简体中文](README-zh-CN.md) | [🇪🇸 Español](README-es.md) | [🇵🇹 Português](README-pt.md) | [🇷🇺 Русский](README-ru.md) | [🇺🇦 Українська](README-uk.md)

Este paquete genera un documento OpenAPI a partir de rutas Symfony, firmas de controladores, atributos Symfony HttpKernel y clases DTO/View tipadas.

El objetivo es mantener la documentación de la API cerca del código de la aplicación. Los endpoints normales no deberían necesitar grandes bloques `#[OA\...]`. Las rutas describen paths y methods, los atributos Symfony describen request mapping, los DTO describen datos de entrada, los view objects describen datos de salida y las route options describen operation metadata. Los fragmentos OpenAPI manuales quedan disponibles para casos excepcionales.

La API pública vive en el namespace `Sunrise\Symfony\OpenApi`.

Lecturas adicionales:

- [Artículo en ruso en Habr](https://habr.com/ru/articles/1047686/) es el tutorial original. Explica qué problema resuelve este paquete y cómo usarlo en una aplicación Symfony.
- [Traducción al inglés en dev.to](https://dev.to/fenric/openapi-without-oa-how-i-built-a-symfony-documentation-generator-45fd) cubre el mismo tutorial en inglés.
- [PHP Annotations plugin para PhpStorm](https://plugins.jetbrains.com/plugin/7320-php-annotations) debería soportar aliases para los attributes de este paquete en próximas versiones, lo que puede mejorar IDE completion y navigation.

## Instalación

```bash
composer require sunrise-studio/symfony-openapi
```

El paquete requiere PHP 8.2 o más nuevo. Las versiones soportadas de los componentes Symfony están definidas en `composer.json`. Symfony 8.1 o más nuevo solo es necesario si tu aplicación quiere usar el atributo runtime nativo `#[Serialize]`.

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

Esto importa dos rutas de documentación:

| Route | Controller | Propósito |
| --- | --- | --- |
| `GET /docs` | `SwaggerController` | Sirve Swagger UI configurado para leer `/docs/openapi.json`. |
| `GET /docs/openapi.json` | `DocumentController` | Sirve el documento OpenAPI JSON generado. |

Estas rutas no se incluyen en el API document generado porque no tienen `api: true` y sus paths no empiezan con `/api/`.

Si solo necesitas una ruta, importa su archivo directamente:

```yaml
openapi_document:
  resource: '@OpenApiBundle/config/routes/document.php'

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
| `openapi.document_uri` | `/docs/openapi.json` | Public URI del documento generado. Swagger UI lo usa para cargar el documento. |
| `openapi.default_timestamp_format` | `OpenApiConfiguration::DEFAULT_TIMESTAMP_FORMAT` | Formato PHP `date()` usado para generar valores OpenAPI `example` para schemas de fecha/hora. |

`SwaggerConfiguration` puede reemplazarse como servicio si necesitas Swagger UI assets o template variables.

### Custom Route Paths

Si solo Swagger UI necesita otro path, define la ruta tú mismo:

```yaml
# config/routes.yaml
swagger_ui:
  path: /swagger.html
  controller: Sunrise\Symfony\OpenApi\Controller\SwaggerController
  methods: [GET]
  options:
    api: false
```

Si también cambia la route del OpenAPI document, actualiza la ruta y `openapi.document_uri` para que Swagger UI cargue el documento correcto:

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

## Construir El Documento

Ejecuta:

```bash
php bin/console openapi:build-document
```

El comando lee la colección de rutas, conserva las routes que deben documentarse, construye el OpenAPI document y lo escribe en `openapi.document_filename`.

Después de generar, con las rutas por defecto del paquete importadas:

- `/docs` abre Swagger UI.
- `/docs/openapi.json` devuelve el JSON document generado.

## Route Options

Route options son el lugar por defecto para operation metadata:

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

Options soportadas:

| Option | Type | Propósito |
| --- | --- | --- |
| `tag`, `tags` | `string\|string[]` | OpenAPI operation tags. |
| `summary` | `string` | OpenAPI operation summary. |
| `description` | `string` | OpenAPI operation description. |
| `deprecated`, `is_deprecated`, `isDeprecated` | `bool` | Marca una operation como deprecated. |
| `api`, `is_api`, `isApi` | `bool` | Incluye o excluye la route del generated document. |
| `response_code` | `int` | Documented response status cuando `#[Serialize]` no proporciona uno. Defaults: `200` para response body y `204` para `void` explícito. |
| `response_format` | `string` | Response format para el documented response body, convertido a media type, por ejemplo `json` a `application/json`. |
| `response_formats` | `string[]` | Varios response formats. Se ignora cuando `response_format` está definido. |

Si no se define ninguna API option, las routes cuyo path empieza con `/api/` se tratan como API routes.

Si tu proyecto no quiere guardar tags, summaries, descriptions y API filtering en route options, reemplaza `RouteMetadataResolverInterface`.

## Symfony Attributes

El paquete entiende los Symfony controller attributes que describen request data. Consulta la [documentación de Symfony](https://symfony.com/doc/current/controller/value_resolver.html).

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

Sin `key`, el nombre del parámetro es el nombre del parámetro PHP y el objeto usa `style: form`. Con `key`, ese valor se usa como nombre del parámetro y el objeto usa `style: deepObject`.

### Request Body

`#[MapRequestPayload]` crea un OpenAPI `requestBody`.

```php
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;

public function create(#[MapRequestPayload(acceptFormat: 'json')] CreatePetRequest $request): PetView
{
    // ...
}
```

Request body generado:

- El PHP parameter type se convierte en request schema.
- `acceptFormat` es opcional. Si se omite, se usa el route default `_format`; si `_format` tampoco existe, se usa `json`.
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

Escribe los controller return types como debe verse la API pública.

| Return type del controlador | Generated response |
| --- | --- |
| View object, DTO, scalar, array | JSON response body. La schema se lee del return type del método. Status por defecto `200`. |
| `void` explícito | Empty response. Status por defecto `204`. |
| Symfony `Response` subclass | Response body no se genera automáticamente. Usa `#[Operation]` si la response debe documentarse manualmente. |

Para APIs JSON, no necesitas response format option. Usa route options solo cuando los defaults no encajan con el endpoint:

- `response_code` cambia el documented status, por ejemplo `201` para create actions.
- `response_format` documenta un response format no estándar.
- `response_formats` documenta varios response formats.

```php
#[Route('/api/pets/{id}', methods: ['GET'])]
public function show(int $id): PetView
{
    // ...
}
```

Symfony 8.1 introdujo [`#[Serialize]`](https://symfony.com/blog/new-in-symfony-8-1-serialize-attribute), que serializa controller results en runtime. Cuando ese atributo está presente, el paquete lee `Serialize::code`; la schema sigue saliendo del PHP return type.

```php
use Symfony\Component\HttpKernel\Attribute\Serialize;

#[Route('/api/pets', methods: ['POST'], options: ['response_code' => 201])]
#[Serialize(code: 201)]
public function create(CreatePetRequest $request): PetView
{
    // ...
}
```

Usa un return type explícito `void` para actions sin response body:

```php
#[Route('/api/pets/{id}', methods: ['DELETE'])]
public function delete(int $id): void
{
    // ...
}
```

Esto documenta el endpoint como una response vacía `204`. Symfony no convierte por sí mismo un resultado `null` del controlador en `204`, así que las aplicaciones que usan actions `void` deben resolverlo en runtime.

Si la aplicación todavía no puede usar Symfony 8.1, un pequeño listener para `KernelEvents::VIEW` puede cubrir ambos casos: `null` se convierte en `204`, y otros controller results se serializan como JSON. La implementación de Symfony es [`SerializeControllerResultAttributeListener`](https://github.com/symfony/http-kernel/blob/ad1426284c2e7fe10de65dc68a25a724639e3838/EventListener/SerializeControllerResultAttributeListener.php); una versión mínima JSON-only puede ser así:

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

Si tu proyecto tiene reglas distintas para response status o formats, reemplaza `ResponseMetadataResolverInterface`.

## OpenAPI Attributes

El paquete proporciona pequeños OpenAPI attributes para casos donde los PHP types no son suficientes:

| Attribute | Target | Propósito |
| --- | --- | --- |
| `#[Operation]` | class, method | Añade un manual OpenAPI operation fragment. |
| `#[ItemType]` | property, parameter | Describe array item type. |
| `#[SchemaName]` | class | Sobrescribe component schema name. |
| `#[PropertyName]` | property | Sobrescribe OpenAPI property name. |
| `#[IgnoreProperty]` | property | Excluye una property del object schema. |
| `#[TimestampFormat]` | property | Sobrescribe date/time example format. |

Array item types normalmente se leen desde PHPDoc:

```php
/** @var list<PetView> */
public array $pets;
```

Se soportan `PetView[]`, `list<PetView>`, `array<PetView>` y `array<string, PetView>`. Nullable item types como `array<PetView|null>` también se soportan. Item types demasiado amplios o ambiguos, como `array<mixed>` y `array<PetView|ErrorView>`, se ignoran. Usa `#[ItemType]` cuando necesites una sobrescritura explícita o un item limit; tiene prioridad sobre `@var`.

## Fragmentos OpenAPI Manuales

La mayoría de endpoints no deberían necesitar OpenAPI fragments manuales. Para casos excepcionales, usa `#[Operation]`:

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

El fragment se fusiona con la generated operation.

### Documentar Errores

Recomendamos mantener el API predecible: una action exitosa debe devolver un view object documentado, y los errores deben usar una error shape documentada en lugar de quedar ocultos en ramas del controller.

Para un error response común, describe un `default` response con `#[Operation]` o con `openapi.initial_operation`:

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

En arrays YAML/PHP, el valor de schema puede ser un PHP type string. Se trata como PHP type cuando el string contiene `\`. Para una clase sin namespace, usa un backslash inicial, por ejemplo `\AppErrorView`. En PHP attributes, usa `new Type(ErrorView::class)` cuando necesites un type object explícito.

## PHP Type Schema Resolvers

La generación de schemas por defecto cubre PHP types comunes:

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

Si tu proyecto tiene un PHP type custom que necesita un custom schema, implementa `OpenApiPhpTypeSchemaResolverInterface` y registra el resolver en `OpenApiPhpTypeSchemaResolverManagerInterface`.

## Object Schemas

DTOs y view objects se describen desde typed properties.

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
- array item types se leen desde `@var` cuando es posible;
- `#[ItemType]` describe array properties explícitamente y tiene prioridad sobre `@var`;
- `#[TimestampFormat]` cambia date/time examples.

Este resolver no usa Symfony Serializer metadata. No lee serializer groups, getters, setters, `SerializedName`, name converters ni reglas de conversión camelCase/snake_case.

Recomendamos DTO y View classes explícitas con typed properties. Si necesitas otra forma externa, crea un nuevo View object y mapea tu domain object a él. Esto mantiene simples la búsqueda, el refactoring y la schema generation.

Si tu equipo necesita first-class Symfony Serializer support, abre un issue. Consideraremos añadirlo como optional resolver o strategy.

Referencia Symfony Serializer: [Serializer](https://symfony.com/doc/current/serializer.html).

## Puntos De Extensión

El paquete está formado por servicios reemplazables para proyectos con convenciones propias:

| Service/interface | Propósito |
| --- | --- |
| `RouteMetadataResolverInterface` | Controla tags, summary, description, deprecation y API filtering. |
| `ResponseMetadataResolverInterface` | Controla response status codes y response formats. |
| `OpenApiOperationEnricherInterface` | Añade request parameters, request bodies, responses o custom operation data. |
| `OpenApiPhpTypeSchemaResolverInterface` | Convierte PHP types en OpenAPI schemas. |
| `OpenApiPathBuilderInterface` | Convierte Symfony route paths en OpenAPI paths. |

Reemplaza estos servicios en el Symfony container cuando las reglas del proyecto difieran de los defaults.
