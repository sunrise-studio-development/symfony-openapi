# OpenAPI Generator for Symfony Routing

[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/sunrise-studio-development/symfony-openapi/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/sunrise-studio-development/symfony-openapi/?branch=master)
[![Code Coverage](https://scrutinizer-ci.com/g/sunrise-studio-development/symfony-openapi/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/sunrise-studio-development/symfony-openapi/?branch=master)
[![Build Status](https://scrutinizer-ci.com/g/sunrise-studio-development/symfony-openapi/badges/build.png?b=master)](https://scrutinizer-ci.com/g/sunrise-studio-development/symfony-openapi/build-status/master)
[![Code Intelligence Status](https://scrutinizer-ci.com/g/sunrise-studio-development/symfony-openapi/badges/code-intelligence.svg?b=master)](https://scrutinizer-ci.com/code-intelligence)

Языки: [🇬🇧 English](README.md) | [🇨🇳 简体中文](README-zh-CN.md) | [🇪🇸 Español](README-es.md) | [🇵🇹 Português](README-pt.md) | [🇷🇺 Русский](README-ru.md) | [🇺🇦 Українська](README-uk.md)

Настоящий пакет генерирует OpenAPI-документ из маршрутов Symfony, сигнатур контроллеров, атрибутов Symfony HttpKernel и типизированных DTO/View классов.

Цель пакета — держать API-документацию рядом с кодом приложения. Обычные endpoints не должны требовать больших блоков `#[OA\...]`. Маршруты описывают paths и методы, Symfony attributes описывают request mapping, DTO описывают входные данные, view objects описывают выходные данные, а route options описывают metadata операции. Ручные OpenAPI-фрагменты остаются для исключительных случаев.

API живет в namespace `Sunrise\Symfony\OpenApi`. Внутри пакет использует OpenAPI engine из [Sunrise HTTP Router](https://github.com/sunrise-php/http-router).

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

Это импортирует два маршрута:

| Route | Controller | Назначение |
| --- | --- | --- |
| `GET /openapi` | `OpenApiController` | Отдает сгенерированный OpenAPI JSON document. |
| `GET /swagger.html` | `SwaggerController` | Отдает Swagger UI, настроенный на `/openapi`. |

Эти маршруты не попадают в генерируемый API document: у них не задано `api: true`, а их paths не начинаются с `/api/`.

Если нужен только один маршрут, импортируйте его файл напрямую:

```yaml
openapi_document:
  resource: '@OpenApiBundle/config/routes/openapi.php'

swagger_ui:
  resource: '@OpenApiBundle/config/routes/swagger.php'
```

Документация Symfony:

- [Routing](https://symfony.com/doc/current/routing.html)
- [Bundles](https://symfony.com/doc/current/bundles.html)

## Конфигурация

Типовая конфигурация приложения:

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

Полезные параметры:

| Параметр | По умолчанию | Назначение |
| --- | --- | --- |
| `openapi.initial_document` | OpenAPI version + `API` title | Базовый документ, с которым объединяются generated paths и schemas. |
| `openapi.initial_operation` | `responses: []` | Базовая operation, которая объединяется с каждой generated operation. |
| `openapi.document_filename` | `%kernel.project_dir%/var/openapi.json` | Output file для `openapi:build-document`. |
| `openapi.default_timestamp_format` | `OpenApiConfiguration::DEFAULT_TIMESTAMP_FORMAT` | Формат PHP `date()` для генерации OpenAPI `example` у схем даты/времени. |
| `openapi.default_empty_response_status` | `204` | Статус по умолчанию для controller methods с `void`. |
| `openapi.default_response_status` | `200` | Статус по умолчанию для сериализуемых return objects. |
| `openapi.default_response_formats` | `['json']` | Symfony response formats по умолчанию для сериализуемых return objects. |

`SwaggerConfiguration` можно заменить как сервис, если нужны свои Swagger UI assets, template variables или другой OpenAPI URL.

## Генерация Документа

Запустите:

```bash
php bin/console openapi:build-document
```

Команда читает коллекцию маршрутов, получает metadata маршрутов, оставляет API routes, строит OpenAPI document и записывает его в `openapi.document_filename`.

После генерации:

- `/openapi` возвращает сгенерированный JSON document.
- `/swagger.html` открывает Swagger UI.

## Route Options

Route options — стандартный источник route-level OpenAPI metadata:

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

| Option | Type | Назначение |
| --- | --- | --- |
| `tags` | `string\|string[]` | OpenAPI operation tags. |
| `summary` | `string` | OpenAPI operation summary. |
| `description` | `string` | OpenAPI operation description. |
| `deprecated`, `is_deprecated`, `isDeprecated` | `bool` | Помечает operation как deprecated. |
| `api`, `is_api`, `isApi` | `bool` | Включает route в generated document или исключает его. |
| `response_status` | `int` | Переопределяет generated response status. |
| `response_formats` | `string\|string[]` | Symfony response formats, например `json` или `xml`. |

Если API option не задан, маршруты с path `/api/...` считаются API routes.

Если route options не подходят вашему проекту как источник metadata, замените `RouteMetadataResolverInterface`.

## Symfony Attributes

Пакет поддерживает Symfony controller value resolver attributes. См. [документацию Symfony](https://symfony.com/doc/current/controller/value_resolver.html).

### Path Variables

Symfony path variables читаются из скомпилированных маршрутов. Requirements превращаются в OpenAPI schema patterns.

```php
#[Route('/api/pets/{petId}', requirements: ['petId' => '\d+'])]
public function show(int $petId): PetView
{
    // ...
}
```

Поддерживаемые reflected parameter types для path variables:

- `bool`
- `int`
- `float`
- `string`
- `BackedEnum`
- `DateTimeInterface`
- `Symfony\Component\Uid\AbstractUid`

Symfony route mapping aliases поддерживаются для простых mappings, например `['id' => 'petId']`. Entity-style mappings вроде `{id:pet.id}` не описываются как object schemas; публичная path variable документируется как string, если не найден поддерживаемый scalar parameter.

### Query Parameter

`#[MapQueryParameter]` описывает scalar, enum, date/time, UID или array query parameters.

```php
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;

public function find(
    #[MapQueryParameter] PetStatus $status,
    #[MapQueryParameter] string ...$tags,
): JsonResponse {
    // ...
}
```

Variadic parameters описываются как arrays и не помечаются required.

### Query Object

`#[MapQueryString]` описывает query object.

```php
use Symfony\Component\HttpKernel\Attribute\MapQueryString;

public function list(#[MapQueryString] PetSearchQuery $query): JsonResponse
{
    // ...
}
```

Если `key` равен `null`, объект описывается как весь query string с `style: form`. Если `key` задан, параметр использует `style: deepObject`.

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

- PHP type параметра становится request schema.
- `acceptFormat` опционален. Если он не задан, используются default accept formats; по умолчанию это `json`.
- `acceptFormat` преобразуется из Symfony request format в media type, например `json` в `application/json`.
- Если PHP parameter required, OpenAPI request body тоже required.
- Для array payload `MapRequestPayload(type: SomeDto::class)` описывает item type.

### Uploaded Files

`#[MapUploadedFile]` добавляет `multipart/form-data` request body с binary fields.

```php
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpKernel\Attribute\MapUploadedFile;

public function upload(#[MapUploadedFile(name: 'photo')] UploadedFile $file): JsonResponse
{
    // ...
}
```

Variadic uploaded files описываются как array of binary strings и не помечаются required.

### Date And Time

`#[MapDateTime(format: ...)]` меняет generated date/time example для параметров контроллера.

```php
use Symfony\Component\HttpKernel\Attribute\MapDateTime;

public function history(#[MapDateTime(format: 'Y-m-d')] DateTimeImmutable $date): JsonResponse
{
    // ...
}
```

Аргумент `format` опционален. Если он не задан, используется default timestamp format.

## Генерация Ответов

Генерация responses по умолчанию намеренно ограничена:

| Return type контроллера | Generated response |
| --- | --- |
| `void` | Empty response, default status `204`. |
| Symfony `Response` subclass | Automatic response content не генерируется. Используйте `#[Operation]`, если response нужно описать вручную. |
| Любой другой named return type | Serialized response body, default status `200`, default format `json`. |

Пример:

```php
#[Route('/api/pets/{id}', options: ['response_status' => 200])]
public function show(int $id): PetView
{
    // ...
}
```

Если route возвращает custom view object, return type используется как response schema.

Если проект оборачивает responses, например `{data: ..., meta: ...}`, замените `ResponseMetadataResolverInterface` или response operation enrichers.

## OpenAPI-Атрибуты

Пакет предоставляет OpenAPI attributes для типовых schema tasks:

| Attribute | Target | Назначение |
| --- | --- | --- |
| `#[Operation]` | class, method | Добавляет manual OpenAPI operation fragment. |
| `#[ItemType]` | property, parameter | Описывает array item type. |
| `#[SchemaName]` | class | Переопределяет component schema name. |
| `#[PropertyName]` | property | Переопределяет OpenAPI property name. |
| `#[IgnoreProperty]` | property | Исключает property из object schema. |
| `#[TimestampFormat]` | property | Переопределяет date/time example format. |

## Ручные OpenAPI-Фрагменты

Большинству endpoints не нужны ручные OpenAPI fragments. Для исключительных случаев используйте `#[Operation]`:

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

Фрагмент объединяется с generated operation.

## PHP Type Schema Resolvers

Зарегистрированные resolvers:

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

Если проекту нужна custom schema для своего PHP type, реализуйте `OpenApiPhpTypeSchemaResolverInterface` и зарегистрируйте resolver в сервисе `OpenApiPhpTypeSchemaResolverManagerInterface`.

## Object Schema Resolver

`ObjectPhpTypeSchemaResolver` — основной resolver для DTO и view objects.

Он читает PHP classes напрямую:

- поддерживаются instantiable non-internal classes;
- public, protected и private properties рефлектятся;
- property types становятся OpenAPI property schemas;
- properties без default value помечаются required;
- scalar и backed enum default values добавляются в schema;
- constructor-promoted property defaults поддерживаются;
- `#[SchemaName]` меняет component schema name;
- `#[PropertyName]` меняет property name;
- `#[IgnoreProperty]` исключает property;
- `#[ItemType]` описывает array properties;
- `#[TimestampFormat]` меняет date/time examples.

Этот resolver не использует Symfony Serializer metadata. Он не читает serializer groups, getters, setters, `SerializedName`, name converters или camelCase/snake_case conversion rules.

Мы рекомендуем явные DTO и View classes с типизированными properties. Если нужна другая внешняя форма, создайте новый View object и замапьте в него domain object. Это упрощает поиск, refactoring и schema generation.

Если вашей команде нужна first-class поддержка Symfony Serializer, откройте issue. Мы рассмотрим ее как optional resolver или strategy.

Документация Symfony Serializer: [Serializer](https://symfony.com/doc/current/serializer.html).

## Точки Расширения

Пакет собран из заменяемых сервисов:

| Service/interface | Назначение |
| --- | --- |
| `RouteMetadataResolverInterface` | Управляет tags, summary, description, deprecation и API filtering. |
| `ResponseMetadataResolverInterface` | Управляет response status и response formats. |
| `OpenApiOperationEnricherInterface` | Добавляет request parameters, request bodies, responses или custom operation data. |
| `OpenApiPhpTypeSchemaResolverInterface` | Преобразует PHP types в OpenAPI schemas. |
| `OpenApiPathBuilderInterface` | Преобразует Symfony route paths в OpenAPI paths. |

Заменяйте эти сервисы в Symfony container, если правила проекта отличаются от defaults.
