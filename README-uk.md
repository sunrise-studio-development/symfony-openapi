# OpenAPI Generator for Symfony Routing

![PHP](https://img.shields.io/packagist/dependency-v/sunrise-studio/symfony-openapi/php?style=social&logo=php&label=PHP)
![Coverage](https://img.shields.io/scrutinizer/coverage/g/sunrise-studio-development/symfony-openapi?style=social)
![Code quality](https://img.shields.io/scrutinizer/quality/g/sunrise-studio-development/symfony-openapi?style=social)

Мови: [English](README.md) | [Русский](README-ru.md) | [Українська](README-uk.md) | [Français](README-fr.md) | [Deutsch](README-de.md)

`sunrise-studio/symfony-openapi` генерує OpenAPI-документ із маршрутів Symfony, сигнатур контролерів, атрибутів Symfony HTTP Kernel та PHP DTO/View класів.

Наша мета проста: розробники застосунків не повинні писати великі блоки OpenAPI-атрибутів для звичайних API endpoints. Ми вважаємо, що документація має йти за кодом, який уже описує API: маршрути, input DTO, query objects, uploaded files, path variables та response view objects. Ручні OpenAPI-фрагменти потрібні тільки для виняткових випадків.

Пакет побудований на OpenAPI-механізмах [Sunrise HTTP Router](https://github.com/sunrise-php/http-router), але Symfony-facing API живе в namespace `Sunrise\Symfony\OpenApi`.

## Встановлення

```bash
composer require sunrise-studio/symfony-openapi
```

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

Це додає два маршрути:

| Route | Controller | Призначення |
| --- | --- | --- |
| `GET /openapi` | `OpenApiController` | Віддає згенерований OpenAPI JSON document. |
| `GET /swagger.html` | `SwaggerController` | Віддає Swagger UI, налаштований на `/openapi`. |

Обидва маршрути зареєстровані з `api: false`, тому вони не потрапляють у згенеровану API-документацію.

Якщо потрібен тільки один маршрут, імпортуйте відповідний файл напряму:

```yaml
openapi_document:
  resource: '@OpenApiBundle/config/routes/openapi.php'

swagger_ui:
  resource: '@OpenApiBundle/config/routes/swagger.php'
```

Документація Symfony:

- [Routing](https://symfony.com/doc/current/routing.html)
- [Bundles](https://symfony.com/doc/current/bundles.html)

## Базова конфігурація

Типова конфігурація застосунку:

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

Корисні параметри:

| Параметр | За замовчуванням | Призначення |
| --- | --- | --- |
| `openapi.initial_document` | OpenAPI version + `API` title | Базовий документ, до якого додаються paths та schemas. |
| `openapi.initial_operation` | `responses: []` | Базова operation для кожного маршруту. |
| `openapi.document_filename` | `%kernel.project_dir%/var/openapi.json` | Файл, у який команда записує згенерований документ. |
| `openapi.default_timestamp_format` | `OpenApiConfiguration::DEFAULT_TIMESTAMP_FORMAT` | Формат PHP `date()` для генерації OpenAPI `example` у схем дати/часу. |
| `openapi.default_empty_response_status` | `204` | Статус за замовчуванням для controller methods з `void`. |
| `openapi.default_response_status` | `200` | Статус за замовчуванням для серіалізованих return objects. |
| `openapi.default_response_formats` | `['json']` | Формати Symfony за замовчуванням для серіалізованих return objects. |

`SwaggerConfiguration` також можна замінити або налаштувати як сервіс, якщо потрібні власні Swagger UI assets, template variables або інший OpenAPI URL.

## Генерація документа

Запустіть:

```bash
php bin/console openapi:build-document
```

Команда читає колекцію маршрутів Symfony, отримує metadata маршрутів, залишає тільки API routes, адаптує їх для Sunrise OpenAPI document builder і зберігає документ у `openapi.document_filename`.

Після цього:

- `/openapi` повертає згенерований JSON document.
- `/swagger.html` відкриває Swagger UI.

## Route Options

Route options є стандартним способом описати route-level metadata:

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

Підтримувані route options:

| Option | Type | Призначення |
| --- | --- | --- |
| `tags` | `string|string[]` | OpenAPI operation tags. |
| `summary` | `string` | OpenAPI operation summary. |
| `description` | `string` | OpenAPI operation description. |
| `deprecated`, `is_deprecated`, `isDeprecated` | `bool` | Позначає operation як deprecated. |
| `api`, `is_api`, `isApi` | `bool` | Включає або виключає route зі згенерованого документа. |
| `response_status` | `int` | Перевизначає згенерований response status. |
| `response_formats` | `string|string[]` | Symfony response formats, наприклад `json` або `xml`. |

Якщо API option не задано, маршрути з path `/api/...` вважаються API routes.

Якщо ви не хочете зберігати tags, summaries, descriptions або API filtering у route options, замініть `RouteMetadataResolverInterface` власним сервісом.

## Symfony Attributes

Пакет підтримує Symfony controller value resolver attributes. Див. [документацію Symfony](https://symfony.com/doc/current/controller/value_resolver.html).

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
- `acceptFormat` перетворюється через Symfony request format на media type, наприклад `json` на `application/json`.
- Якщо параметр є required у PHP, OpenAPI request body також позначається як required.
- Для array payload `MapRequestPayload(type: SomeDto::class)` описує item type.

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

### Query Parameter

`#[MapQueryParameter]` описує scalar, enum, date, UID або array query parameters.

```php
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;

public function find(
    #[MapQueryParameter] PetStatus $status,
    #[MapQueryParameter] string ...$tags,
): JsonResponse {
    // ...
}
```

Variadic parameters описуються як arrays і не позначаються як required.

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

Variadic uploaded files описуються як array of binary strings і не позначаються як required.

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

Symfony route mapping aliases підтримуються для простих mappings, наприклад `['id' => 'petId']`. Entity-style mappings на кшталт `{id:pet.id}` навмисно не описуються як object schemas; публічна path variable все одно документується як string, якщо не знайдено підтримуваний scalar parameter.

### Date and Time

`#[MapDateTime(format: ...)]` змінює згенерований date/time example для параметра контролера.

```php
use Symfony\Component\HttpKernel\Attribute\MapDateTime;

public function history(#[MapDateTime(format: 'Y-m-d')] DateTimeImmutable $date): JsonResponse
{
    // ...
}
```

## Response Generation

Поведінка responses за замовчуванням навмисно невелика й передбачувана:

| Controller return type | Generated response |
| --- | --- |
| `void` | Empty response, default status `204`. |
| Symfony `Response` subclass | Автоматичний response content не генерується. Використовуйте `#[Operation]` для ручних випадків. |
| Будь-який інший named return type | Serialized response body, default status `200`, default format `json`. |

Приклад:

```php
#[Route('/api/pets/{id}', options: ['response_status' => 200])]
public function show(int $id): PetView
{
    // ...
}
```

Якщо route повертає custom view object, return type проходить через PHP type schema resolver system і використовується як response schema.

Якщо проєкт обгортає responses, наприклад `{data: ..., meta: ...}`, замініть `ResponseMetadataResolverInterface` або response operation enrichers власними сервісами.

## Manual OpenAPI Fragments

Більшість endpoints не повинні потребувати ручних OpenAPI fragments. Для виняткових випадків використовуйте `#[Operation]`:

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

Фрагмент merge-иться в generated operation через Sunrise `OpenApiDocumentManager`.

## Symfony OpenAPI Annotations

Пакет надає Symfony-facing annotations, щоб application code не імпортував router або hydrator namespaces для типових OpenAPI schema tasks:

| Annotation | Target | Призначення |
| --- | --- | --- |
| `#[Operation]` | class, method | Додає manual OpenAPI operation fragment. |
| `#[ItemType]` | property, parameter | Описує array item type. |
| `#[SchemaName]` | class | Перевизначає component schema name. |
| `#[PropertyName]` | property | Перевизначає OpenAPI property name. |
| `#[IgnoreProperty]` | property | Виключає property з object schema. |
| `#[TimestampFormat]` | property | Перевизначає date/time example format. |

## PHP Type Schema Resolution

Bundle явно реєструє Sunrise schema resolvers і замінює timestamp resolver на Symfony-aware реалізацію.

Активні resolvers:

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

Якщо проєкту потрібна custom schema для власного типу, реалізуйте `Sunrise\Http\Router\OpenApi\OpenApiPhpTypeSchemaResolverInterface` і зареєструйте resolver у сервісі `OpenApiPhpTypeSchemaResolverManagerInterface`.

## Object Schema Resolver

`Sunrise\Http\Router\OpenApi\PhpTypeSchemaResolver\ObjectPhpTypeSchemaResolver` є основним resolver для DTO та view objects.

Він читає PHP classes напряму:

- підтримуються instantiable non-internal classes;
- public/private/protected properties рефлектяться напряму;
- property types стають OpenAPI property schemas;
- properties без default value позначаються як required;
- property default values додаються, якщо це scalar або backed enum;
- constructor-promoted property defaults підтримуються;
- `#[SchemaName]` змінює component schema name;
- `#[PropertyName]` змінює property name;
- `#[IgnoreProperty]` виключає property;
- `#[ItemType]` описує array properties;
- `#[TimestampFormat]` змінює date/time examples.

Цей resolver не використовує Symfony Serializer metadata. Він не читає serializer groups, getters, setters, `SerializedName`, name converters або camelCase/snake_case conversion rules.

Ми рекомендуємо явні DTO та View classes з типізованими properties. Якщо потрібна інша зовнішня форма, створіть новий View object і замапте в нього domain object. Це спрощує пошук, refactoring та schema generation.

Якщо вашій команді потрібна first-class підтримка Symfony Serializer, відкрийте issue. Ми розглянемо її як optional resolver або strategy layer.

Документація Symfony Serializer: [Serializer](https://symfony.com/doc/current/serializer.html).

## Extension Points

Пакет навмисно складається з невеликих сервісів:

| Service/interface | Призначення |
| --- | --- |
| `RouteMetadataResolverInterface` | Керує tags, summary, description, deprecation та API filtering. |
| `ResponseMetadataResolverInterface` | Керує response status та response formats. |
| `OpenApiOperationEnricherInterface` | Додає request parameters, request bodies, responses або custom operation data. |
| `OpenApiPhpTypeSchemaResolverInterface` | Перетворює PHP types на OpenAPI schemas. |
| `OpenApiPathBuilderInterface` | Перетворює Symfony route paths на OpenAPI paths. |

Ці сервіси можна замінити у Symfony container, якщо правила проєкту відрізняються від defaults.

## Навіщо існує цей пакет

Ми бачили багато API, документованих довгими ручними OpenAPI attribute blocks. Це працює, але документація часто стає другою реалізацією того самого API.

Ми хочемо інший нормальний шлях:

- routes описують paths та HTTP methods;
- Symfony attributes описують request mapping;
- DTO описують input payloads;
- view objects описують output payloads;
- route options описують human operation metadata;
- OpenAPI-specific code використовується тільки тоді, коли automatic model недостатньо.

Чим ближче документація до реального application code, тим складніше їм розійтися.
