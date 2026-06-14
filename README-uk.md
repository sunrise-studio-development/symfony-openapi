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

Пакету потрібен Symfony HttpKernel 8.1 або новіший.

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
| `openapi.document_uri` | `/openapi` | Public URI згенерованого документа. Swagger UI використовує його для завантаження документа. |
| `openapi.default_timestamp_format` | `OpenApiConfiguration::DEFAULT_TIMESTAMP_FORMAT` | Формат PHP `date()` для генерації OpenAPI `example` у схемах дати/часу. |

`SwaggerConfiguration` можна замінити як сервіс, якщо потрібні власні Swagger UI assets або template variables.

### Custom Route Paths

Якщо інший path потрібен тільки для Swagger UI, визначте маршрут самостійно:

```yaml
# config/routes.yaml
swagger_ui:
  path: /docs
  controller: Sunrise\Symfony\OpenApi\Controller\SwaggerController
  methods: [GET]
  options:
    api: false
```

Якщо змінюється і route OpenAPI document, оновіть сам маршрут і `openapi.document_uri`, щоб Swagger UI завантажував правильний документ:

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

## Генерація Документа

Запустіть:

```bash
php bin/console openapi:build-document
```

Команда читає колекцію маршрутів, залишає маршрути, які мають бути задокументовані, будує OpenAPI document і записує його в `openapi.document_filename`.

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
| `response_code` | `int` | Response status code. Використовується для `void` responses і serialized responses без `#[Serialize]`. |
| `response_format` | `string` | Response format, що перетворюється на media type, наприклад `json` на `application/json`. |
| `response_formats` | `string[]` | Response formats. Ігнорується, якщо задано `response_format`. |

Якщо API option не задано, маршрути з path `/api/...` вважаються API routes.

Якщо проєкт не хоче зберігати tags, summary, description і API filtering у route options, замініть `RouteMetadataResolverInterface`.

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
- `acceptFormat` опціональний. Якщо він не заданий, використовується route default `_format`; якщо `_format` теж відсутній, використовується `json`.
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

Пишіть return types контролерів так, як має виглядати публічний API.

| Return type контролера | Generated response |
| --- | --- |
| View object, DTO, scalar, array | JSON response body. Schema береться з return type методу. Status за замовчуванням `200`. |
| Явний `void` | Empty response. Status за замовчуванням `204`. |
| Symfony `Response` subclass | Response body автоматично не генерується. Використовуйте `#[Operation]`, якщо response потрібно описати вручну. |

Для JSON API response format option не потрібен. Використовуйте route options тільки коли defaults не підходять:

- `response_code` змінює documented status, наприклад `201` для create actions.
- `response_format` описує один нестандартний Symfony response format.
- `response_formats` описує кілька response formats.

```php
#[Route('/api/pets/{id}', methods: ['GET'])]
public function show(int $id): PetView
{
    // ...
}
```

У Symfony 8.1 з'явився [`#[Serialize]`](https://symfony.com/blog/new-in-symfony-8-1-serialize-attribute), який серіалізує результат контролера в runtime. Пакет читає `Serialize::code`, якщо атрибут є; schema все одно береться з PHP return type.

```php
use Symfony\Component\HttpKernel\Attribute\Serialize;

#[Route('/api/pets', methods: ['POST'], options: ['response_code' => 201])]
#[Serialize(code: 201)]
public function create(CreatePetRequest $request): PetView
{
    // ...
}
```

Для actions без response body використовуйте явний return type `void`:

```php
#[Route('/api/pets/{id}', methods: ['DELETE'])]
public function delete(int $id): void
{
    // ...
}
```

Це тільки документує API. Сам Symfony не перетворює `null` result контролера на `204`. Якщо застосунок використовує `void` actions, додайте маленький listener на `KernelEvents::VIEW`.

Якщо поки не можна використовувати Symfony 8.1, цей самий listener може серіалізувати non-null controller results у JSON. Реалізація Symfony: [`SerializeControllerResultAttributeListener`](https://github.com/symfony/http-kernel/blob/ad1426284c2e7fe10de65dc68a25a724639e3838/EventListener/SerializeControllerResultAttributeListener.php); мінімальна JSON-only версія може бути такою:

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

Якщо проєкт не хоче читати response status і format з `#[Serialize]` та route options, замініть `ResponseMetadataResolverInterface`.

## OpenAPI-Атрибути

Пакет надає OpenAPI attributes для типових schema tasks:

| Attribute | Target | Призначення |
| --- | --- | --- |
| `#[Operation]` | class, method | Додає manual OpenAPI operation fragment. |
| `#[ItemType]` | property, parameter | Описує array item type. |
| `#[SchemaName]` | class | Перевизначає component schema name. |
| `#[PropertyName]` | property | Перевизначає OpenAPI property name. |
| `#[IgnoreProperty]` | property | Виключає property з object schema. |
| `#[TimestampFormat]` | property | Перевизначає date/time example format. |

## Ручні OpenAPI-Фрагменти

Більшості endpoints не потрібні ручні OpenAPI fragments. Для виняткових випадків використовуйте `#[Operation]`:

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

Фрагмент об'єднується з generated operation.

### Документування Помилок

Ми рекомендуємо робити API передбачуваним: успішний action має повертати один документований view object, а помилки мають мати документовану error shape, а не ховатися в гілках контролера.

Для спільного error response опишіть `default` response через `#[Operation]` або через `openapi.initial_operation`:

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

У YAML/PHP-масивах значення schema може бути PHP type string. Воно вважається PHP type, якщо рядок містить `\`. Для класу без namespace використовуйте початковий backslash, наприклад `\AppErrorView`. У PHP attributes використовуйте `new Type(ErrorView::class)`, коли потрібен явний type object.

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

## Object Schemas

DTO і View objects описуються за типізованими PHP properties. Всередині за це відповідає `ObjectPhpTypeSchemaResolver`.

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
| `ResponseMetadataResolverInterface` | Керує response status codes і response formats. |
| `OpenApiOperationEnricherInterface` | Додає request parameters, request bodies, responses або custom operation data. |
| `OpenApiPhpTypeSchemaResolverInterface` | Перетворює PHP types на OpenAPI schemas. |
| `OpenApiPathBuilderInterface` | Перетворює Symfony route paths на OpenAPI paths. |

Замінюйте ці сервіси в Symfony container, якщо правила проєкту відрізняються від defaults.
