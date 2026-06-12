# OpenAPI Generator for Symfony Routing

![PHP](https://img.shields.io/packagist/dependency-v/sunrise-studio/symfony-openapi/php?style=social&logo=php&label=PHP)
![Coverage](https://img.shields.io/scrutinizer/coverage/g/sunrise-studio-development/symfony-openapi?style=social)
![Code quality](https://img.shields.io/scrutinizer/quality/g/sunrise-studio-development/symfony-openapi?style=social)

Языки: [English](README.md) | [Русский](README-ru.md) | [Українська](README-uk.md) | [Français](README-fr.md) | [Deutsch](README-de.md)

`sunrise-studio/symfony-openapi` генерирует OpenAPI-документ из маршрутов Symfony, сигнатур контроллеров, атрибутов Symfony HTTP Kernel и PHP DTO/View классов.

Наша цель проста: разработчик приложения не должен писать большие блоки OpenAPI-атрибутов для обычных API endpoints. Мы считаем, что документация должна следовать уже существующему коду, который описывает API: маршрутам, input DTO, query objects, uploaded files, path variables и response view objects. Ручные OpenAPI-фрагменты нужны только для исключительных случаев.

Пакет построен на OpenAPI-механизмах [Sunrise HTTP Router](https://github.com/sunrise-php/http-router), но Symfony-facing API живет в namespace `Sunrise\Symfony\OpenApi`.

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

Оба маршрута зарегистрированы с `api: false`, поэтому они не попадают в генерируемую API-документацию.

Если нужен только один маршрут, импортируйте конкретный route-файл напрямую:

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

Типовая конфигурация приложения:

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

Полезные параметры:

| Параметр | По умолчанию | Назначение |
| --- | --- | --- |
| `openapi.initial_document` | OpenAPI version + `API` title | Базовый документ, к которому добавляются generated paths и schemas. |
| `openapi.initial_operation` | `responses: []` | Базовая operation для каждого маршрута. |
| `openapi.document_filename` | `%kernel.project_dir%/var/openapi.json` | Файл, куда команда записывает сгенерированный документ. |
| `openapi.default_timestamp_format` | `OpenApiConfiguration::DEFAULT_TIMESTAMP_FORMAT` | Формат PHP `date()` для генерации OpenAPI `example` у схем даты/времени. |
| `openapi.default_empty_response_status` | `204` | Статус по умолчанию для controller methods с `void`. |
| `openapi.default_response_status` | `200` | Статус по умолчанию для сериализуемых return objects. |
| `openapi.default_response_formats` | `['json']` | Форматы Symfony по умолчанию для сериализуемых return objects. |

`SwaggerConfiguration` можно заменить или настроить как сервис, если нужны свои Swagger UI assets, template variables или другой OpenAPI URL.

## Генерация документа

Запустите:

```bash
php bin/console openapi:build-document
```

Команда читает коллекцию маршрутов Symfony, получает metadata маршрутов, оставляет только API routes, адаптирует их для Sunrise OpenAPI document builder и сохраняет документ в `openapi.document_filename`.

После этого:

- `/openapi` возвращает сгенерированный JSON document.
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

Поддерживаемые route options:

| Option | Type | Назначение |
| --- | --- | --- |
| `tags` | `string|string[]` | OpenAPI operation tags. |
| `summary` | `string` | OpenAPI operation summary. |
| `description` | `string` | OpenAPI operation description. |
| `deprecated`, `is_deprecated`, `isDeprecated` | `bool` | Помечает operation как deprecated. |
| `api`, `is_api`, `isApi` | `bool` | Включает route в документ или исключает его. |
| `response_status` | `int` | Переопределяет сгенерированный response status. |
| `response_formats` | `string|string[]` | Symfony response formats, например `json` или `xml`. |

Если API option не задан, маршруты с path `/api/...` считаются API routes.

Если вы не хотите хранить tags, summaries, descriptions или API filtering в route options, замените `RouteMetadataResolverInterface` своим сервисом.

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

- PHP type параметра становится request schema.
- `acceptFormat` преобразуется через Symfony request format в media type, например `json` в `application/json`.
- Если параметр required в PHP, OpenAPI request body тоже помечается required.
- Для array payload `MapRequestPayload(type: SomeDto::class)` описывает item type.

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

### Query Parameter

`#[MapQueryParameter]` описывает scalar, enum, date, UID или array query parameters.

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

Symfony route mapping aliases поддерживаются для простых mappings, например `['id' => 'petId']`. Entity-style mappings вроде `{id:pet.id}` намеренно не описываются как object schemas; публичная path variable все равно документируется как string, если не найден поддерживаемый scalar parameter.

### Date and Time

`#[MapDateTime(format: ...)]` меняет сгенерированный date/time example для параметров контроллера.

```php
use Symfony\Component\HttpKernel\Attribute\MapDateTime;

public function history(#[MapDateTime(format: 'Y-m-d')] DateTimeImmutable $date): JsonResponse
{
    // ...
}
```

## Response Generation

Поведение responses по умолчанию намеренно маленькое и предсказуемое:

| Controller return type | Generated response |
| --- | --- |
| `void` | Empty response, default status `204`. |
| Symfony `Response` subclass | Автоматический response content не генерируется. Используйте `#[Operation]` для ручных случаев. |
| Любой другой named return type | Serialized response body, default status `200`, default format `json`. |

Пример:

```php
#[Route('/api/pets/{id}', options: ['response_status' => 200])]
public function show(int $id): PetView
{
    // ...
}
```

Если route возвращает custom view object, return type проходит через PHP type schema resolver system и используется как response schema.

Если проект оборачивает responses, например `{data: ..., meta: ...}`, замените `ResponseMetadataResolverInterface` или response operation enrichers своими сервисами.

## Manual OpenAPI Fragments

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

Фрагмент merge-ится в generated operation через Sunrise `OpenApiDocumentManager`.

## Symfony OpenAPI Annotations

Пакет предоставляет Symfony-facing annotations, чтобы application code не импортировал router или hydrator namespaces для типовых OpenAPI schema tasks:

| Annotation | Target | Назначение |
| --- | --- | --- |
| `#[Operation]` | class, method | Добавляет manual OpenAPI operation fragment. |
| `#[ItemType]` | property, parameter | Описывает array item type. |
| `#[SchemaName]` | class | Переопределяет component schema name. |
| `#[PropertyName]` | property | Переопределяет OpenAPI property name. |
| `#[IgnoreProperty]` | property | Исключает property из object schema. |
| `#[TimestampFormat]` | property | Переопределяет date/time example format. |

## PHP Type Schema Resolution

Bundle явно регистрирует Sunrise schema resolvers и заменяет timestamp resolver на Symfony-aware реализацию.

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

Если проекту нужна custom schema для своего типа, реализуйте `Sunrise\Http\Router\OpenApi\OpenApiPhpTypeSchemaResolverInterface` и зарегистрируйте resolver в сервисе `OpenApiPhpTypeSchemaResolverManagerInterface`.

## Object Schema Resolver

`Sunrise\Http\Router\OpenApi\PhpTypeSchemaResolver\ObjectPhpTypeSchemaResolver` — основной resolver для DTO и view objects.

Он читает PHP classes напрямую:

- поддерживаются instantiable non-internal classes;
- public/private/protected properties рефлектятся напрямую;
- property types становятся OpenAPI property schemas;
- properties без default value помечаются required;
- property default values добавляются, если это scalar или backed enum;
- constructor-promoted property defaults поддерживаются;
- `#[SchemaName]` меняет component schema name;
- `#[PropertyName]` меняет property name;
- `#[IgnoreProperty]` исключает property;
- `#[ItemType]` описывает array properties;
- `#[TimestampFormat]` меняет date/time examples.

Этот resolver не использует Symfony Serializer metadata. Он не читает serializer groups, getters, setters, `SerializedName`, name converters или camelCase/snake_case conversion rules.

Мы рекомендуем явные DTO и View classes с типизированными properties. Если нужна другая внешняя форма, создайте новый View object и замапьте в него domain object. Это упрощает поиск, refactoring и schema generation.

Если вашей команде нужна first-class поддержка Symfony Serializer, откройте issue. Мы рассмотрим ее как optional resolver или strategy layer.

Документация Symfony Serializer: [Serializer](https://symfony.com/doc/current/serializer.html).

## Extension Points

Пакет намеренно собран из маленьких сервисов:

| Service/interface | Назначение |
| --- | --- |
| `RouteMetadataResolverInterface` | Управляет tags, summary, description, deprecation и API filtering. |
| `ResponseMetadataResolverInterface` | Управляет response status и response formats. |
| `OpenApiOperationEnricherInterface` | Добавляет request parameters, request bodies, responses или custom operation data. |
| `OpenApiPhpTypeSchemaResolverInterface` | Преобразует PHP types в OpenAPI schemas. |
| `OpenApiPathBuilderInterface` | Преобразует Symfony route paths в OpenAPI paths. |

Эти сервисы можно заменить в Symfony container, если правила проекта отличаются от defaults.

## Зачем существует этот пакет

Мы видели много API, документированных длинными ручными OpenAPI attribute blocks. Это работает, но документация часто становится второй реализацией того же самого API.

Мы хотим другой нормальный путь:

- routes описывают paths и HTTP methods;
- Symfony attributes описывают request mapping;
- DTO описывают input payloads;
- view objects описывают output payloads;
- route options описывают human operation metadata;
- OpenAPI-specific code используется только там, где automatic model недостаточно.

Чем ближе документация к реальному application code, тем сложнее им разойтись.
