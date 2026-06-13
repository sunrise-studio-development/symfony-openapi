# OpenAPI Generator for Symfony Routing

[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/sunrise-studio-development/symfony-openapi/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/sunrise-studio-development/symfony-openapi/?branch=master)
[![Code Coverage](https://scrutinizer-ci.com/g/sunrise-studio-development/symfony-openapi/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/sunrise-studio-development/symfony-openapi/?branch=master)
[![Build Status](https://scrutinizer-ci.com/g/sunrise-studio-development/symfony-openapi/badges/build.png?b=master)](https://scrutinizer-ci.com/g/sunrise-studio-development/symfony-openapi/build-status/master)
[![Code Intelligence Status](https://scrutinizer-ci.com/g/sunrise-studio-development/symfony-openapi/badges/code-intelligence.svg?b=master)](https://scrutinizer-ci.com/code-intelligence)

Мови: [🇬🇧 English](README.md) | [🇨🇳 简体中文](README-zh-CN.md) | [🇪🇸 Español](README-es.md) | [🇵🇹 Português](README-pt.md) | [🇷🇺 Русский](README-ru.md) | [🇺🇦 Українська](README-uk.md)

Цей пакет генерує OpenAPI-документ із маршрутів Symfony, сигнатур контролерів, атрибутів Symfony HttpKernel та типізованих DTO/View класів.

Мета пакета — тримати API-документацію близько до коду застосунку. Звичайні endpoints не повинні вимагати великих блоків `#[OA\...]`. Маршрути описують paths і methods, Symfony attributes описують request mapping, DTO описують вхідні дані, view objects описують вихідні дані, а route options описують metadata операції. Ручні OpenAPI-фрагменти залишаються для виняткових випадків.

API живе в namespace `Sunrise\Symfony\OpenApi`. Всередині пакет використовує OpenAPI engine з [Sunrise HTTP Router](https://github.com/sunrise-php/http-router).

## Встановлення

```bash
composer require sunrise-studio/symfony-openapi
```

Пакету потрібен `symfony/http-kernel` 8.1 або новіший, тому що документація responses використовує Symfony attribute `#[Serialize]`.

Підключіть bundle:

```php
// config/bundles.php
return [
    Symfony\Bundle\FrameworkBundle\FrameworkBundle::class => ['all' => true],
    Sunrise\Symfony\OpenApi\OpenApiBundle::class => ['all' => true],
];
```

Імпортуйте маршрути пакета:

```yaml
# config/routes.yaml
openapi:
  resource: '@OpenApiBundle/config/routes.php'
```

Це імпортує два маршрути:

| Route | Controller | Призначення |
| --- | --- | --- |
| `GET /openapi` | `DocumentController` | Віддає згенерований OpenAPI JSON document. |
| `GET /swagger.html` | `SwaggerController` | Віддає Swagger UI, налаштований на `/openapi`. |

Ці маршрути не потрапляють у generated API document: для них не задано `api: true`, а їхні paths не починаються з `/api/`.

Якщо потрібен тільки один маршрут, імпортуйте його файл напряму:

```yaml
openapi_document:
  resource: '@OpenApiBundle/config/routes/document.php'

swagger_ui:
  resource: '@OpenApiBundle/config/routes/swagger.php'
```

Документація Symfony:

- [Routing](https://symfony.com/doc/current/routing.html)
- [Bundles](https://symfony.com/doc/current/bundles.html)

## Конфігурація

Типова конфігурація застосунку:

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

Корисні параметри:

| Параметр | За замовчуванням | Призначення |
| --- | --- | --- |
| `openapi.initial_document` | OpenAPI version + `API` title | Базовий документ, з яким об'єднуються generated paths і schemas. |
| `openapi.initial_operation` | `responses: []` | Базова operation, що об'єднується з кожною generated operation. |
| `openapi.document_filename` | `%kernel.project_dir%/var/openapi.json` | Output file для `openapi:build-document`. |
| `openapi.default_timestamp_format` | `OpenApiConfiguration::DEFAULT_TIMESTAMP_FORMAT` | Формат PHP `date()` для генерації OpenAPI `example` у схемах дати/часу. |

`SwaggerConfiguration` можна замінити як сервіс, якщо потрібні власні Swagger UI assets, template variables або інший OpenAPI URL.

## Генерація Документа

Запустіть:

```bash
php bin/console openapi:build-document
```

Команда читає колекцію маршрутів, отримує metadata маршрутів, залишає API routes, будує OpenAPI document і записує його в `openapi.document_filename`.

Після генерації:

- `/openapi` повертає згенерований JSON document.
- `/swagger.html` відкриває Swagger UI.

## Route Options

Route options — стандартне джерело route-level OpenAPI metadata:

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

Підтримувані options:

| Option | Type | Призначення |
| --- | --- | --- |
| `tags` | `string\|string[]` | OpenAPI operation tags. |
| `summary` | `string` | OpenAPI operation summary. |
| `description` | `string` | OpenAPI operation description. |
| `deprecated`, `is_deprecated`, `isDeprecated` | `bool` | Позначає operation як deprecated. |
| `api`, `is_api`, `isApi` | `bool` | Включає route у generated document або виключає його. |

Якщо API option не задано, маршрути з path `/api/...` вважаються API routes.

Якщо route options не підходять вашому проєкту як джерело metadata, замініть `RouteMetadataResolverInterface`.

## Symfony Attributes

Пакет підтримує Symfony controller value resolver attributes. Див. [документацію Symfony](https://symfony.com/doc/current/controller/value_resolver.html).

### Path Variables

Symfony path variables читаються зі скомпільованих маршрутів. Requirements перетворюються на OpenAPI schema patterns.

```php
#[Route('/api/pets/{petId}', requirements: ['petId' => '\d+'])]
public function show(int $petId): PetView
{
    // ...
}
```

Підтримувані reflected parameter types для path variables:

- `bool`
- `int`
- `float`
- `string`
- `BackedEnum`
- `DateTimeInterface`
- `Symfony\Component\Uid\AbstractUid`

Symfony route mapping aliases підтримуються для простих mappings, наприклад `['id' => 'petId']`. Entity-style mappings на кшталт `{id:pet.id}` не описуються як object schemas; публічна path variable документується як string, якщо не знайдено підтримуваний scalar parameter.

### Query Parameter

`#[MapQueryParameter]` описує scalar, enum, date/time, UID або array query parameters.

```php
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;

public function find(
    #[MapQueryParameter] PetStatus $status,
    #[MapQueryParameter] string ...$tags,
): JsonResponse {
    // ...
}
```

Variadic parameters описуються як arrays і не позначаються required.

### Query Object

`#[MapQueryString]` описує query object.

```php
use Symfony\Component\HttpKernel\Attribute\MapQueryString;

public function list(#[MapQueryString] PetSearchQuery $query): JsonResponse
{
    // ...
}
```

Якщо `key` дорівнює `null`, об'єкт описується як увесь query string з `style: form`. Якщо `key` задано, параметр використовує `style: deepObject`.

### Request Body

`#[MapRequestPayload]` створює OpenAPI `requestBody`.

```php
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;

public function create(#[MapRequestPayload(acceptFormat: 'json')] CreatePetRequest $request): PetView
{
    // ...
}
```

Поведінка:

- PHP type параметра стає request schema.
- `acceptFormat` опціональний. Якщо він не заданий, використовуються default accept formats; за замовчуванням це `json`.
- `acceptFormat` перетворюється із Symfony request format на media type, наприклад `json` на `application/json`.
- Якщо PHP parameter required, OpenAPI request body також required.
- Для array payload `MapRequestPayload(type: SomeDto::class)` описує item type.

### Uploaded Files

`#[MapUploadedFile]` додає `multipart/form-data` request body з binary fields.

```php
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpKernel\Attribute\MapUploadedFile;

public function upload(#[MapUploadedFile(name: 'photo')] UploadedFile $file): JsonResponse
{
    // ...
}
```

Variadic uploaded files описуються як array of binary strings і не позначаються required.

### Date And Time

`#[MapDateTime(format: ...)]` змінює generated date/time example для параметрів контролера.

```php
use Symfony\Component\HttpKernel\Attribute\MapDateTime;

public function history(#[MapDateTime(format: 'Y-m-d')] DateTimeImmutable $date): JsonResponse
{
    // ...
}
```

Аргумент `format` опціональний. Якщо він не заданий, використовується default timestamp format.

## Генерація Відповідей

Пакет описує responses тільки тоді, коли контролер явно показує, як повертається результат.

| Metadata контролера | Generated response |
| --- | --- |
| `#[Serialize]` | Serialized response body. Status береться з `Serialize::code`; schema береться з return type методу. |
| `#[EmptyResponse]` | Empty response. Status за замовчуванням `204`. |
| Symfony `Response` subclass без OpenAPI attributes | Automatic response content не генерується. Використовуйте `#[Operation]` або `#[EmptyResponse]`, якщо response потрібно описати. |

Serialized responses використовують route default `_format` як Symfony response format. Якщо `_format` не задано, використовується `json`. Format перетворюється на media type через `Request::getMimeTypes()`.

```php
use Symfony\Component\HttpKernel\Attribute\Serialize;

#[Route('/api/pets/{id}', format: 'json')]
#[Serialize(code: 200)]
public function show(int $id): PetView
{
    // ...
}
```

Якщо route повертає custom view object, `#[Serialize]` робить return type response schema.

Для actions без response body використовуйте `#[EmptyResponse]`:

```php
use Sunrise\Symfony\OpenApi\Annotation\EmptyResponse;

#[Route('/api/pets/{id}', methods: ['DELETE'])]
#[EmptyResponse]
public function delete(int $id): void
{
    // ...
}
```

Для wrappers на кшталт `{data: ..., meta: ...}` додайте custom operation enricher або опишіть response через `#[Operation]`.

## OpenAPI-Атрибути

Пакет надає OpenAPI attributes для типових schema tasks:

| Attribute | Target | Призначення |
| --- | --- | --- |
| `#[Operation]` | class, method | Додає manual OpenAPI operation fragment. |
| `#[EmptyResponse]` | class, method | Додає empty OpenAPI response, за замовчуванням `204`. |
| `#[ItemType]` | property, parameter | Описує array item type. |
| `#[SchemaName]` | class | Перевизначає component schema name. |
| `#[PropertyName]` | property | Перевизначає OpenAPI property name. |
| `#[IgnoreProperty]` | property | Виключає property з object schema. |
| `#[TimestampFormat]` | property | Перевизначає date/time example format. |

## Ручні OpenAPI-Фрагменти

Більшості endpoints не потрібні ручні OpenAPI fragments. Для виняткових випадків використовуйте `#[Operation]`:

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

Фрагмент об'єднується з generated operation.

## PHP Type Schema Resolvers

Зареєстровані resolvers:

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

Якщо проєкту потрібна custom schema для власного PHP type, реалізуйте `OpenApiPhpTypeSchemaResolverInterface` і зареєструйте resolver у сервісі `OpenApiPhpTypeSchemaResolverManagerInterface`.

## Object Schema Resolver

`ObjectPhpTypeSchemaResolver` — основний resolver для DTO і view objects.

Він читає PHP classes напряму:

- підтримуються instantiable non-internal classes;
- public, protected і private properties рефлектяться;
- property types стають OpenAPI property schemas;
- properties без default value позначаються required;
- scalar і backed enum default values додаються в schema;
- constructor-promoted property defaults підтримуються;
- `#[SchemaName]` змінює component schema name;
- `#[PropertyName]` змінює property name;
- `#[IgnoreProperty]` виключає property;
- `#[ItemType]` описує array properties;
- `#[TimestampFormat]` змінює date/time examples.

Цей resolver не використовує Symfony Serializer metadata. Він не читає serializer groups, getters, setters, `SerializedName`, name converters або camelCase/snake_case conversion rules.

Ми рекомендуємо явні DTO і View classes з типізованими properties. Якщо потрібна інша зовнішня форма, створіть новий View object і замапте в нього domain object. Це спрощує пошук, refactoring і schema generation.

Якщо вашій команді потрібна first-class підтримка Symfony Serializer, відкрийте issue. Ми розглянемо її як optional resolver або strategy.

Документація Symfony Serializer: [Serializer](https://symfony.com/doc/current/serializer.html).

## Точки Розширення

Пакет складається із замінних сервісів:

| Service/interface | Призначення |
| --- | --- |
| `RouteMetadataResolverInterface` | Керує tags, summary, description, deprecation і API filtering. |
| `OpenApiOperationEnricherInterface` | Додає request parameters, request bodies, responses або custom operation data. |
| `OpenApiPhpTypeSchemaResolverInterface` | Перетворює PHP types на OpenAPI schemas. |
| `OpenApiPathBuilderInterface` | Перетворює Symfony route paths на OpenAPI paths. |

Замінюйте ці сервіси в Symfony container, якщо правила проєкту відрізняються від defaults.
