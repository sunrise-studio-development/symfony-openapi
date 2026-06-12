# OpenAPI Generator for Symfony Routing

![PHP](https://img.shields.io/packagist/dependency-v/sunrise-studio/symfony-openapi/php?style=social&logo=php&label=PHP)
![Coverage](https://img.shields.io/scrutinizer/coverage/g/sunrise-studio-development/symfony-openapi?style=social)
![Code quality](https://img.shields.io/scrutinizer/quality/g/sunrise-studio-development/symfony-openapi?style=social)

Мови: [English](README.md) | [Русский](README-ru.md) | [Українська](README-uk.md) | [Français](README-fr.md) | [Deutsch](README-de.md)

`sunrise-studio/symfony-openapi` генерує OpenAPI-документ із Symfony routes, сигнатур контролерів, атрибутів Symfony HTTP Kernel та PHP DTO/View класів.

Наша мета проста: розробник застосунку не повинен писати великі блоки OpenAPI-атрибутів для звичайних API endpoint-ів. Документація має йти за кодом, який уже описує API: routes, input DTO, query objects, uploaded files, path variables та response view objects.

Пакет побудований на OpenAPI-механізмах [Sunrise HTTP Router](https://github.com/sunrise-php/http-router), але Symfony API живе в namespace `Sunrise\Symfony\OpenApi`.

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

Маршрути:

| Route | Controller | Призначення |
| --- | --- | --- |
| `GET /openapi` | `OpenApiController` | Віддає згенерований OpenAPI JSON. |
| `GET /swagger.html` | `SwaggerController` | Віддає Swagger UI для `/openapi`. |

Обидва маршрути мають `api: false`, тому не потрапляють у згенеровану документацію.

Можна імпортувати тільки потрібний файл:

```yaml
openapi_document:
  resource: '@OpenApiBundle/config/routes/openapi.php'

swagger_ui:
  resource: '@OpenApiBundle/config/routes/swagger.php'
```

Документація Symfony: [Routing](https://symfony.com/doc/current/routing.html), [Bundles](https://symfony.com/doc/current/bundles.html).

## Конфігурація

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

Основні параметри:

| Параметр | За замовчуванням | Призначення |
| --- | --- | --- |
| `openapi.initial_document` | OpenAPI version + `API` title | Базовий документ. |
| `openapi.initial_operation` | `responses: []` | Базова operation для кожного route. |
| `openapi.document_filename` | `%kernel.project_dir%/var/openapi.json` | Файл для збереження документа. |
| `openapi.default_timestamp_format` | Sunrise default | Формат timestamp examples. |
| `openapi.default_empty_response_status` | `204` | Status для `void`. |
| `openapi.default_response_status` | `200` | Status для serialized return objects. |
| `openapi.default_response_formats` | `['json']` | Default response formats. |

`SwaggerConfiguration` можна замінити як сервіс, якщо потрібні інші assets, template variables або OpenAPI URL.

## Команда

```bash
php bin/console openapi:build-document
```

Команда читає Symfony `RouterInterface`, фільтрує API routes, будує документ і зберігає його в `openapi.document_filename`.

## Route Options

```php
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

Підтримуються:

- `tags`
- `summary`
- `description`
- `deprecated`, `is_deprecated`, `isDeprecated`
- `api`, `is_api`, `isApi`
- `response_status`
- `response_formats`

Якщо API option не задано, path з `/api/` вважається API route. Для іншої політики замініть `RouteMetadataResolverInterface`.

## Symfony Attributes

Пакет підтримує Symfony value resolver attributes: [Symfony docs](https://symfony.com/doc/current/controller/value_resolver.html).

- `#[MapRequestPayload]` створює `requestBody`, використовує PHP type параметра та `acceptFormat`.
- `#[MapQueryString]` описує query object; `key: null` дає `style: form`, заданий `key` дає `deepObject`.
- `#[MapQueryParameter]` описує query parameter; variadic стає array і не є required.
- `#[MapUploadedFile]` описує `multipart/form-data`; variadic файли стають array of binary і не є required.
- Path variables беруться з Symfony route; requirements стають OpenAPI patterns.
- `#[MapDateTime(format: ...)]` змінює timestamp example.

## Responses

| Return type | Response |
| --- | --- |
| `void` | Empty response, default `204`. |
| Symfony `Response` subclass | Автоматично не описується. Використовуйте `#[Operation]`. |
| Інший named return type | Serialized response body, default `200`, default `json`. |

`response_status` і `response_formats` перевизначають defaults. Для власної response-політики замініть `ResponseMetadataResolverInterface` або response enrichers.

## Annotations

| Annotation | Призначення |
| --- | --- |
| `#[Operation]` | Ручний OpenAPI operation fragment. |
| `#[ItemType]` | Item type для arrays. |
| `#[SchemaName]` | Component schema name. |
| `#[PropertyName]` | OpenAPI property name. |
| `#[IgnoreProperty]` | Виключає property. |
| `#[TimestampFormat]` | Timestamp example format. |

## PHP Type Schema Resolution

Активні resolvers: bool, int, float, string, array, ArrayAccess collections, backed enums, objects, Symfony UID/UUID, timestamp.

Для власних типів реалізуйте `Sunrise\Http\Router\OpenApi\OpenApiPhpTypeSchemaResolverInterface` і зареєструйте resolver у container.

## Object Schema Resolver

`ObjectPhpTypeSchemaResolver` читає PHP properties напряму. Він підтримує required fields, default values, promoted constructor defaults, schema/property names, ignored properties, array item types і timestamp formats.

Symfony Serializer metadata не використовується. Groups, getters, setters, `SerializedName`, name converters і camelCase/snake_case rules не читаються.

Ми рекомендуємо явні DTO/View класи з типізованими properties. Якщо потрібна підтримка Symfony Serializer, відкрийте issue.

Документація Symfony Serializer: [Serializer](https://symfony.com/doc/current/serializer.html).

## Extension Points

- `RouteMetadataResolverInterface`
- `ResponseMetadataResolverInterface`
- `OpenApiOperationEnricherInterface`
- `OpenApiPhpTypeSchemaResolverInterface`
- `OpenApiPathBuilderInterface`

Ці сервіси можна замінити у Symfony container.

## Навіщо цей пакет

Ми не хочемо, щоб OpenAPI-документація ставала другою ручною реалізацією API. Routes, Symfony attributes, DTO та View objects уже описують більшість API. Пакет використовує ці джерела і залишає ручний OpenAPI тільки для виняткових випадків.
