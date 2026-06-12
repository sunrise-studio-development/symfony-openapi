# OpenAPI Generator for Symfony Routing

![PHP](https://img.shields.io/packagist/dependency-v/sunrise-studio/symfony-openapi/php?style=social&logo=php&label=PHP)
![Coverage](https://img.shields.io/scrutinizer/coverage/g/sunrise-studio-development/symfony-openapi?style=social)
![Code quality](https://img.shields.io/scrutinizer/quality/g/sunrise-studio-development/symfony-openapi?style=social)

Языки: [English](README.md) | [Русский](README-ru.md) | [Українська](README-uk.md) | [Français](README-fr.md) | [Deutsch](README-de.md)

`sunrise-studio/symfony-openapi` генерирует OpenAPI-документ из Symfony routes, сигнатур контроллеров, атрибутов Symfony HTTP Kernel и PHP DTO/View классов.

Наша цель проста: разработчик приложения не должен писать большие блоки OpenAPI-атрибутов для обычных API endpoint-ов. Мы считаем, что документация должна следовать уже существующему коду: routes, input DTO, query objects, uploaded files, path variables и response view objects. Ручные OpenAPI-фрагменты нужны только для исключительных случаев.

Пакет построен на OpenAPI-механизмах [Sunrise HTTP Router](https://github.com/sunrise-php/http-router), но Symfony API живет в namespace `Sunrise\Symfony\OpenApi`.

## Установка

```bash
composer require sunrise-studio/symfony-openapi
```

Подключите bundle:

```php
// config/bundles.php
return [
    Symfony\Bundle\FrameworkBundle\FrameworkBundle::class => ['all' => true],
    Sunrise\Symfony\OpenApi\OpenApiBundle::class => ['all' => true],
];
```

Импортируйте маршруты пакета:

```yaml
# config/routes.yaml
openapi:
  resource: '@OpenApiBundle/config/routes.php'
```

Будут добавлены два маршрута:

| Route | Controller | Назначение |
| --- | --- | --- |
| `GET /openapi` | `OpenApiController` | Отдает сгенерированный OpenAPI JSON. |
| `GET /swagger.html` | `SwaggerController` | Отдает Swagger UI, настроенный на `/openapi`. |

Оба маршрута зарегистрированы с `api: false`, поэтому они не попадают в генерируемую документацию.

Если нужен только один маршрут:

```yaml
openapi_document:
  resource: '@OpenApiBundle/config/routes/openapi.php'

swagger_ui:
  resource: '@OpenApiBundle/config/routes/swagger.php'
```

Документация Symfony:

- [Routing](https://symfony.com/doc/current/routing.html)
- [Bundles](https://symfony.com/doc/current/bundles.html)

## Базовая конфигурация

Пример конфигурации приложения:

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

Основные параметры:

| Параметр | По умолчанию | Назначение |
| --- | --- | --- |
| `openapi.initial_document` | OpenAPI version + `API` title | Базовый документ, к которому добавляются paths и schemas. |
| `openapi.initial_operation` | `responses: []` | Базовая operation для каждого маршрута. |
| `openapi.document_filename` | `%kernel.project_dir%/var/openapi.json` | Файл, куда команда сохраняет документ. |
| `openapi.default_timestamp_format` | Sunrise default timestamp format | Формат примеров для `DateTimeImmutable`. |
| `openapi.default_empty_response_status` | `204` | Статус для controller methods с `void`. |
| `openapi.default_response_status` | `200` | Статус для сериализуемых return objects. |
| `openapi.default_response_formats` | `['json']` | Форматы Symfony для сериализуемых return objects. |

`SwaggerConfiguration` можно заменить как сервис, если нужны другие Swagger UI assets, template variables или OpenAPI URL.

## Генерация документа

```bash
php bin/console openapi:build-document
```

Команда читает Symfony `RouterInterface`, получает metadata маршрутов, оставляет только API routes, адаптирует их для Sunrise OpenAPI builder и сохраняет документ в `openapi.document_filename`.

После генерации:

- `/openapi` возвращает JSON-документ;
- `/swagger.html` открывает Swagger UI.

## Route Options

Route options — основной способ описывать route-level metadata:

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

Поддерживаемые options:

| Option | Тип | Назначение |
| --- | --- | --- |
| `tags` | `string|string[]` | OpenAPI operation tags. |
| `summary` | `string` | OpenAPI operation summary. |
| `description` | `string` | OpenAPI operation description. |
| `deprecated`, `is_deprecated`, `isDeprecated` | `bool` | Помечает operation deprecated. |
| `api`, `is_api`, `isApi` | `bool` | Включает или исключает route из документа. |
| `response_status` | `int` | Переопределяет response status. |
| `response_formats` | `string|string[]` | Symfony formats, например `json` или `xml`. |

Если API option не задан, маршруты с path `/api/...` считаются API routes.

Если вы не хотите хранить tags, summaries, descriptions и API filtering в route options, замените `RouteMetadataResolverInterface`.

## Symfony Attributes

Пакет поддерживает Symfony controller value resolver attributes. См. [документацию Symfony](https://symfony.com/doc/current/controller/value_resolver.html).

### Request Body

`#[MapRequestPayload]` создает OpenAPI `requestBody`.

```php
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;

public function create(#[MapRequestPayload(acceptFormat: 'json')] CreatePetRequest $request): PetView
{
    // ...
}
```

Поведение:

- PHP type параметра становится request schema;
- `acceptFormat` преобразуется в media type, например `json` в `application/json`;
- required PHP parameter делает request body required;
- для array payload `MapRequestPayload(type: SomeDto::class)` описывает item type.

### Query Object

`#[MapQueryString]` описывает query object. Если `key` равен `null`, объект описывает весь query string с `style: form`. Если `key` задан, используется `style: deepObject`.

### Query Parameter

`#[MapQueryParameter]` описывает scalar, enum, date, UID или array query parameters. Variadic parameters описываются как arrays и не являются required.

### Uploaded Files

`#[MapUploadedFile]` добавляет `multipart/form-data` request body с binary fields. Variadic uploaded files описываются как array of binary strings и не являются required.

### Path Variables

Path variables читаются из скомпилированных Symfony routes. Requirements превращаются в OpenAPI schema patterns.

Поддерживаемые типы параметров:

- `bool`
- `int`
- `float`
- `string`
- `BackedEnum`
- `DateTimeInterface`
- `Symfony\Component\Uid\AbstractUid`

Простые route mapping aliases поддерживаются, например `['id' => 'petId']`. Entity-style mappings вроде `{id:pet.id}` не описываются как object schemas; публичная переменная маршрута остается строкой, если не найден поддерживаемый scalar parameter.

### Date and Time

`#[MapDateTime(format: ...)]` меняет example для timestamp parameter.

## Response Generation

Поведение по умолчанию:

| Return type контроллера | Response |
| --- | --- |
| `void` | Empty response, default status `204`. |
| Symfony `Response` subclass | Автоматический content не генерируется. Используйте `#[Operation]` для ручных случаев. |
| Любой другой named return type | Serialized response body, default status `200`, default format `json`. |

`response_status` и `response_formats` можно переопределить в route options.

Если проект использует wrapper-ответы, например `{data: ..., meta: ...}`, замените `ResponseMetadataResolverInterface` или response operation enrichers.

## Manual OpenAPI Fragments

Для исключительных случаев используйте `#[Operation]`:

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

Фрагмент merge-ится в operation через Sunrise `OpenApiDocumentManager`.

## Symfony OpenAPI Annotations

| Annotation | Target | Назначение |
| --- | --- | --- |
| `#[Operation]` | class, method | Ручной OpenAPI operation fragment. |
| `#[ItemType]` | property, parameter | Item type для arrays. |
| `#[SchemaName]` | class | Имя component schema. |
| `#[PropertyName]` | property | Имя OpenAPI property. |
| `#[IgnoreProperty]` | property | Исключает property из schema. |
| `#[TimestampFormat]` | property | Формат timestamp example. |

Эти annotations — Symfony-facing wrappers вокруг Sunrise OpenAPI/Hydrator concepts.

## PHP Type Schema Resolution

Активные resolvers:

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

Для своих типов реализуйте `Sunrise\Http\Router\OpenApi\OpenApiPhpTypeSchemaResolverInterface` и зарегистрируйте resolver в `OpenApiPhpTypeSchemaResolverManagerInterface`.

## Object Schema Resolver

`ObjectPhpTypeSchemaResolver` читает PHP classes напрямую:

- поддерживаются instantiable non-internal classes;
- свойства рефлектятся напрямую;
- property types становятся OpenAPI property schemas;
- свойства без default value попадают в `required`;
- scalar/backed enum default values добавляются в schema;
- constructor-promoted defaults поддерживаются;
- `#[SchemaName]`, `#[PropertyName]`, `#[IgnoreProperty]`, `#[ItemType]`, `#[TimestampFormat]` влияют на schema.

Symfony Serializer metadata не используется. Resolver не читает serializer groups, getters, setters, `SerializedName`, name converters и camelCase/snake_case rules.

Мы рекомендуем явные DTO и View classes с типизированными свойствами. Если нужна другая внешняя форма, создайте отдельный View object и замапьте в него domain object.

Если нужна полноценная поддержка Symfony Serializer, откройте issue. Мы рассмотрим optional resolver или strategy layer.

Документация Symfony Serializer: [Serializer](https://symfony.com/doc/current/serializer.html).

## Extension Points

| Service/interface | Назначение |
| --- | --- |
| `RouteMetadataResolverInterface` | Tags, summary, description, deprecation и API filtering. |
| `ResponseMetadataResolverInterface` | Response status и response formats. |
| `OpenApiOperationEnricherInterface` | Request parameters, request bodies, responses или custom operation data. |
| `OpenApiPhpTypeSchemaResolverInterface` | PHP type to OpenAPI schema. |
| `OpenApiPathBuilderInterface` | Symfony route path to OpenAPI path. |

Любой из этих сервисов можно заменить в Symfony container.

## Зачем нужен пакет

Мы часто видели API, где документация написана длинными ручными OpenAPI attributes. Это работает, но документация превращается во вторую реализацию API.

Мы хотим другой путь:

- routes описывают paths и HTTP methods;
- Symfony attributes описывают request mapping;
- DTO описывают input payloads;
- View objects описывают output payloads;
- route options описывают human metadata;
- OpenAPI-specific code используется только там, где автоматической модели не хватает.

Чем ближе документация к реальному коду приложения, тем сложнее им разойтись.
