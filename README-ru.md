# OpenAPI Generator for Symfony Routing

[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/sunrise-studio-development/symfony-openapi/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/sunrise-studio-development/symfony-openapi/?branch=master)
[![Code Coverage](https://scrutinizer-ci.com/g/sunrise-studio-development/symfony-openapi/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/sunrise-studio-development/symfony-openapi/?branch=master)
[![Build Status](https://scrutinizer-ci.com/g/sunrise-studio-development/symfony-openapi/badges/build.png?b=master)](https://scrutinizer-ci.com/g/sunrise-studio-development/symfony-openapi/build-status/master)
[![Code Intelligence Status](https://scrutinizer-ci.com/g/sunrise-studio-development/symfony-openapi/badges/code-intelligence.svg?b=master)](https://scrutinizer-ci.com/code-intelligence)

Языки: [🇬🇧 English](README.md) | [🇨🇳 简体中文](README-zh-CN.md) | [🇪🇸 Español](README-es.md) | [🇵🇹 Português](README-pt.md) | [🇷🇺 Русский](README-ru.md) | [🇺🇦 Українська](README-uk.md)

Настоящий пакет генерирует OpenAPI-документ из маршрутов Symfony, сигнатур контроллеров, атрибутов Symfony HttpKernel и типизированных DTO/View классов.

Цель пакета — держать API-документацию рядом с кодом приложения. Обычные endpoints не должны требовать больших блоков `#[OA\...]`. Маршруты описывают paths и методы, Symfony attributes описывают request mapping, DTO описывают входные данные, view objects описывают выходные данные, а route options описывают metadata операции. Ручные OpenAPI-фрагменты остаются для исключительных случаев.

Публичный API живет в namespace `Sunrise\Symfony\OpenApi`.

Дополнительные материалы:

- [Статья на Хабре](https://habr.com/ru/articles/1047686/) в которой объясняется, какую проблему решает пакет и как использовать его в Symfony-приложении.
- [PHP Annotations plugin для PhpStorm](https://plugins.jetbrains.com/plugin/7320-php-annotations) в ближайших релизах ожидает поддержку алиасов для attributes этого пакета, что должно улучшить IDE completion и navigation.

## Установка

```bash
composer require sunrise-studio/symfony-openapi
```

Пакету нужен PHP 8.2 или новее. Поддерживаемые версии Symfony-компонентов указаны в `composer.json`. Symfony 8.1 или новее нужен только если приложение хочет использовать нативный runtime-атрибут `#[Serialize]`.

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

Это импортирует два документационных маршрута:

| Route | Controller | Назначение |
| --- | --- | --- |
| `GET /docs` | `SwaggerController` | Отдает Swagger UI, настроенный на `/docs/openapi.json`. |
| `GET /docs/openapi.json` | `DocumentController` | Отдает сгенерированный OpenAPI JSON document. |

Эти маршруты не попадают в генерируемый API document, потому что у них не задано `api: true`, а их paths не начинаются с `/api/`.

Если нужен только один маршрут, импортируйте его файл напрямую:

```yaml
openapi_document:
  resource: '@OpenApiBundle/config/routes/document.php'

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
| `openapi.document_uri` | `/docs/openapi.json` | Public URI сгенерированного документа. Swagger UI использует его для загрузки документа. |
| `openapi.default_timestamp_format` | `OpenApiConfiguration::DEFAULT_TIMESTAMP_FORMAT` | Формат PHP `date()` для генерации OpenAPI `example` у схем даты/времени. |

`SwaggerConfiguration` можно заменить как сервис, если нужны свои Swagger UI assets или template variables.

### Custom Route Paths

Если другой path нужен только для Swagger UI, определите маршрут сами:

```yaml
# config/routes.yaml
swagger_ui:
  path: /swagger.html
  controller: Sunrise\Symfony\OpenApi\Controller\SwaggerController
  methods: [GET]
  options:
    api: false
```

Если меняется и route OpenAPI document, обновите сам маршрут и `openapi.document_uri`, чтобы Swagger UI загружал правильный документ:

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

## Генерация Документа

Запустите:

```bash
php bin/console openapi:build-document
```

Команда читает коллекцию маршрутов, оставляет маршруты, которые должны быть задокументированы, строит OpenAPI document и записывает его в `openapi.document_filename`.

После генерации, если подключены стандартные маршруты пакета:

- `/docs` открывает Swagger UI.
- `/docs/openapi.json` возвращает сгенерированный JSON document.

## Route Options

Route options — стандартное место для operation metadata:

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

Поддерживаемые options:

| Option | Type | Назначение |
| --- | --- | --- |
| `tag`, `tags` | `string\|string[]` | OpenAPI operation tags. |
| `summary` | `string` | OpenAPI operation summary. |
| `description` | `string` | OpenAPI operation description. |
| `deprecated`, `is_deprecated`, `isDeprecated` | `bool` | Помечает operation как deprecated. |
| `api`, `is_api`, `isApi` | `bool` | Включает route в generated document или исключает его. |
| `response_code` | `int` | Documented response status, когда `#[Serialize]` не задает свой code. Дефолты: `200` для response body и `204` для явного `void`. |
| `response_format` | `string` | Response format для documented response body, который преобразуется в media type, например `json` в `application/json`. |
| `response_formats` | `string[]` | Несколько response formats. Игнорируется, если задан `response_format`. |

Если API option не задан, маршруты с path `/api/...` считаются API routes.

Если проект не хочет хранить tags, summary, description и API filtering в route options, замените `RouteMetadataResolverInterface`.

## Symfony Attributes

Пакет понимает Symfony controller attributes, которые описывают request data. См. [документацию Symfony](https://symfony.com/doc/current/controller/value_resolver.html).

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

Без `key` имя параметра берется из PHP-параметра, а объект использует `style: form`. С `key` это значение становится именем параметра, а объект использует `style: deepObject`.

### Request Body

`#[MapRequestPayload]` создает OpenAPI `requestBody`.

```php
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;

public function create(#[MapRequestPayload(acceptFormat: 'json')] CreatePetRequest $request): PetView
{
    // ...
}
```

Сгенерированный request body:

- PHP type параметра становится request schema.
- `acceptFormat` опционален. Если он не задан, используется route default `_format`; если `_format` тоже отсутствует, используется `json`.
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

## Responses

Пишите return types контроллеров так, как должен выглядеть публичный API.

| Return type контроллера | Generated response |
| --- | --- |
| View object, DTO, scalar, array | JSON response body. Schema берется из return type метода. Status по умолчанию `200`. |
| Явный `void` | Empty response. Status по умолчанию `204`. |
| Symfony `Response` subclass | Response body автоматически не генерируется. Используйте `#[Operation]`, если response нужно описать вручную. |

Для JSON API response format option не нужен. Используйте route options только когда дефолты не подходят конкретному endpoint:

- `response_code` меняет documented status, например `201` для create actions.
- `response_format` описывает один нестандартный response format.
- `response_formats` описывает несколько response formats.

```php
#[Route('/api/pets/{id}', methods: ['GET'])]
public function show(int $id): PetView
{
    // ...
}
```

В Symfony 8.1 появился [`#[Serialize]`](https://symfony.com/blog/new-in-symfony-8-1-serialize-attribute), который сериализует результат контроллера в runtime. Когда этот атрибут есть, пакет читает `Serialize::code`; schema по-прежнему берется из PHP return type.

```php
use Symfony\Component\HttpKernel\Attribute\Serialize;

#[Route('/api/pets', methods: ['POST'], options: ['response_code' => 201])]
#[Serialize(code: 201)]
public function create(CreatePetRequest $request): PetView
{
    // ...
}
```

Для actions без response body используйте явный return type `void`:

```php
#[Route('/api/pets/{id}', methods: ['DELETE'])]
public function delete(int $id): void
{
    // ...
}
```

Это документирует endpoint как пустой `204` response. Сам Symfony не превращает `null` result контроллера в `204`, поэтому приложения с `void` actions должны обработать это в runtime.

Если приложение пока не может использовать Symfony 8.1, небольшой listener на `KernelEvents::VIEW` может закрыть оба кейса: `null` станет `204`, а остальные controller results будут сериализованы в JSON. Реализация Symfony: [`SerializeControllerResultAttributeListener`](https://github.com/symfony/http-kernel/blob/ad1426284c2e7fe10de65dc68a25a724639e3838/EventListener/SerializeControllerResultAttributeListener.php); минимальная JSON-only версия может быть такой:

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

Если в проекте другие правила для response status или formats, замените `ResponseMetadataResolverInterface`.

## OpenAPI-Атрибуты

Пакет предоставляет небольшие OpenAPI attributes для случаев, когда PHP types недостаточно:

| Attribute | Target | Назначение |
| --- | --- | --- |
| `#[Operation]` | class, method | Добавляет manual OpenAPI operation fragment. |
| `#[ItemType]` | property, parameter | Описывает array item type. |
| `#[SchemaName]` | class | Переопределяет component schema name. |
| `#[PropertyName]` | property | Переопределяет OpenAPI property name. |
| `#[IgnoreProperty]` | property | Исключает property из object schema. |
| `#[TimestampFormat]` | property | Переопределяет date/time example format. |

Array item types обычно читаются из PHPDoc:

```php
/** @var list<PetView> */
public array $pets;
```

Поддерживаются формы `PetView[]`, `list<PetView>`, `array<PetView>` и `array<string, PetView>`. Nullable item types вроде `array<PetView|null>` поддерживаются. Широкие или неоднозначные item types вроде `array<mixed>` и `array<PetView|ErrorView>` игнорируются. Используйте `#[ItemType]`, если нужно явное переопределение или item limit; он имеет приоритет над `@var`.

## Ручные OpenAPI-Фрагменты

Большинству endpoints не нужны ручные OpenAPI fragments. Для исключительных случаев используйте `#[Operation]`:

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

Фрагмент объединяется с generated operation.

### Документирование Ошибок

Мы рекомендуем делать API предсказуемым: успешный action должен возвращать один документированный view object, а ошибки должны иметь документированную error shape, а не прятаться в ветках контроллера.

Для общего error response опишите `default` response через `#[Operation]` или через `openapi.initial_operation`:

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

В YAML/PHP-массивах значение schema может быть PHP type string. Оно считается PHP type, если строка содержит `\`. Для класса без namespace используйте ведущий backslash, например `\AppErrorView`. В PHP attributes используйте `new Type(ErrorView::class)`, когда нужен явный type object.

## PHP Type Schema Resolvers

Дефолтная генерация schemas покрывает распространенные PHP types:

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

Если проекту нужна custom schema для своего PHP type, реализуйте `OpenApiPhpTypeSchemaResolverInterface` и зарегистрируйте resolver в `OpenApiPhpTypeSchemaResolverManagerInterface`.

## Object Schemas

DTO и View objects описываются по типизированным properties.

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
- array item types читаются из `@var`, когда это возможно;
- `#[ItemType]` явно описывает array properties и имеет приоритет над `@var`;
- `#[TimestampFormat]` меняет date/time examples.

Этот resolver не использует Symfony Serializer metadata. Он не читает serializer groups, getters, setters, `SerializedName`, name converters или camelCase/snake_case conversion rules.

Мы рекомендуем явные DTO и View classes с типизированными properties. Если нужна другая внешняя форма, создайте новый View object и замапьте в него domain object. Это упрощает поиск, refactoring и schema generation.

Если вашей команде нужна first-class поддержка Symfony Serializer, откройте issue. Мы рассмотрим ее как optional resolver или strategy.

Документация Symfony Serializer: [Serializer](https://symfony.com/doc/current/serializer.html).

## Точки Расширения

Пакет собран из заменяемых сервисов для проектов со своими conventions:

| Service/interface | Назначение |
| --- | --- |
| `RouteMetadataResolverInterface` | Управляет tags, summary, description, deprecation и API filtering. |
| `ResponseMetadataResolverInterface` | Управляет response status codes и response formats. |
| `OpenApiOperationEnricherInterface` | Добавляет request parameters, request bodies, responses или custom operation data. |
| `OpenApiPhpTypeSchemaResolverInterface` | Преобразует PHP types в OpenAPI schemas. |
| `OpenApiPathBuilderInterface` | Преобразует Symfony route paths в OpenAPI paths. |

Заменяйте эти сервисы в Symfony container, если правила проекта отличаются от defaults.
