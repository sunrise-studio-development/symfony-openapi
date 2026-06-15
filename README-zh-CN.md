# OpenAPI Generator for Symfony Routing

[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/sunrise-studio-development/symfony-openapi/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/sunrise-studio-development/symfony-openapi/?branch=master)
[![Code Coverage](https://scrutinizer-ci.com/g/sunrise-studio-development/symfony-openapi/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/sunrise-studio-development/symfony-openapi/?branch=master)
[![Build Status](https://scrutinizer-ci.com/g/sunrise-studio-development/symfony-openapi/badges/build.png?b=master)](https://scrutinizer-ci.com/g/sunrise-studio-development/symfony-openapi/build-status/master)
[![Code Intelligence Status](https://scrutinizer-ci.com/g/sunrise-studio-development/symfony-openapi/badges/code-intelligence.svg?b=master)](https://scrutinizer-ci.com/code-intelligence)

语言: [🇬🇧 English](README.md) | [🇨🇳 简体中文](README-zh-CN.md) | [🇪🇸 Español](README-es.md) | [🇵🇹 Português](README-pt.md) | [🇷🇺 Русский](README-ru.md) | [🇺🇦 Українська](README-uk.md)

此包根据 Symfony 路由、控制器签名、Symfony HttpKernel attributes，以及带类型的 DTO/View 类生成 OpenAPI 文档。

目标是让 API 文档尽量贴近应用代码。普通 endpoints 不应该需要大量 `#[OA\...]` 代码块。路由描述 paths 和 methods，Symfony attributes 描述 request mapping，DTO 描述输入数据，view objects 描述输出数据，route options 描述 operation metadata。手写 OpenAPI fragments 只用于特殊场景。

公共 API 位于 `Sunrise\Symfony\OpenApi` namespace。

## 安装

```bash
composer require sunrise-studio/symfony-openapi
```

该包需要 PHP 8.2 或更新版本。支持的 Symfony 组件版本定义在 `composer.json` 中。只有当应用想使用 Symfony 原生 runtime 属性 `#[Serialize]` 时，才需要 Symfony 8.1 或更新版本。

注册 bundle:

```php
// config/bundles.php
return [
    Symfony\Bundle\FrameworkBundle\FrameworkBundle::class => ['all' => true],
    Sunrise\Symfony\OpenApi\OpenApiBundle::class => ['all' => true],
];
```

导入包提供的路由:

```yaml
# config/routes.yaml
openapi:
  resource: '@OpenApiBundle/config/routes.php'
```

这会导入两个文档路由:

| Route | Controller | 用途 |
| --- | --- | --- |
| `GET /docs` | `SwaggerController` | 返回配置为读取 `/docs/openapi.json` 的 Swagger UI. |
| `GET /docs/openapi.json` | `DocumentController` | 返回生成的 OpenAPI JSON document. |

这两个路由不会进入生成的 API document：它们没有设置 `api: true`，并且 paths 不以 `/api/` 开头。

如果只需要其中一个路由，可以直接导入对应文件:

```yaml
openapi_document:
  resource: '@OpenApiBundle/config/routes/document.php'

swagger_ui:
  resource: '@OpenApiBundle/config/routes/swagger.php'
```

Symfony 参考文档:

- [Routing](https://symfony.com/doc/current/routing.html)
- [Bundles](https://symfony.com/doc/current/bundles.html)

## 配置

典型应用配置:

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

常用参数:

| Parameter | Default | 用途 |
| --- | --- | --- |
| `openapi.initial_document` | OpenAPI version + `API` title | 与生成的 paths 和 schemas 合并的基础 document. |
| `openapi.initial_operation` | `responses: []` | 与每个 generated operation 合并的基础 operation. |
| `openapi.document_filename` | `%kernel.project_dir%/var/openapi.json` | `openapi:build-document` 使用的 output file. |
| `openapi.document_uri` | `/docs/openapi.json` | 生成文档的 public URI。Swagger UI 使用它加载文档。 |
| `openapi.default_timestamp_format` | `OpenApiConfiguration::DEFAULT_TIMESTAMP_FORMAT` | 用于为 date/time schemas 生成 OpenAPI `example` 的 PHP `date()` 格式. |

如果需要自定义 Swagger UI assets 或 template variables，可以把 `SwaggerConfiguration` 替换为自己的 service。

### Custom Route Paths

如果只有 Swagger UI 需要不同 path，可以自己定义 route:

```yaml
# config/routes.yaml
swagger_ui:
  path: /swagger.html
  controller: Sunrise\Symfony\OpenApi\Controller\SwaggerController
  methods: [GET]
  options:
    api: false
```

如果 OpenAPI document route 也改变，需要同时更新 route 和 `openapi.document_uri`，这样 Swagger UI 才会加载正确文档:

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

## 生成文档

运行:

```bash
php bin/console openapi:build-document
```

该命令读取 route collection，保留应该被文档化的 routes，构建 OpenAPI document，并写入 `openapi.document_filename`。

生成后，如果已导入包默认路由:

- `/docs` 打开 Swagger UI.
- `/docs/openapi.json` 返回生成的 JSON document.

## Route Options

Route options 是 operation metadata 的默认位置:

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

支持的 options:

| Option | Type | 用途 |
| --- | --- | --- |
| `tag`, `tags` | `string\|string[]` | OpenAPI operation tags. |
| `summary` | `string` | OpenAPI operation summary. |
| `description` | `string` | OpenAPI operation description. |
| `deprecated`, `is_deprecated`, `isDeprecated` | `bool` | 将 operation 标记为 deprecated. |
| `api`, `is_api`, `isApi` | `bool` | 将 route 包含到 generated document 中或从中排除. |
| `response_code` | `int` | 当 `#[Serialize]` 没有提供 code 时使用的 documented response status。默认值：带 response body 时为 `200`，显式 `void` 时为 `204`. |
| `response_format` | `string` | documented response body 的 response format，会转换为 media type，例如 `json` 转为 `application/json`. |
| `response_formats` | `string[]` | 多个 response formats。设置了 `response_format` 时忽略. |

如果没有设置 API option，path 以 `/api/` 开头的 routes 会被视为 API routes。

如果项目不想把 tags、summary、description 等信息放在 route options 中，可以替换 `RouteMetadataResolverInterface`。

## Symfony Attributes

该包理解用于描述 request data 的 Symfony controller attributes。参见 Symfony 的 [controller value resolver documentation](https://symfony.com/doc/current/controller/value_resolver.html)。

### Path Variables

Symfony path variables 从编译后的 routes 读取。Requirements 会转换为 OpenAPI schema patterns。

```php
#[Route('/api/pets/{petId}', requirements: ['petId' => '\d+'])]
public function show(int $petId): PetView
{
    // ...
}
```

Path variables 支持的 reflected parameter types:

- `bool`
- `int`
- `float`
- `string`
- `BackedEnum`
- `DateTimeInterface`
- `Symfony\Component\Uid\AbstractUid`

简单的 Symfony route mapping aliases 会被支持，例如 `['id' => 'petId']`。`{id:pet.id}` 这类 entity-style mappings 不会被描述为 object schemas；如果找不到支持的 scalar parameter，公开的 path variable 会被文档化为 string。

### Query Parameter

`#[MapQueryParameter]` 描述 scalar、enum、date/time、UID 或 array query parameters。

```php
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;

public function find(
    #[MapQueryParameter] PetStatus $status,
    #[MapQueryParameter] string ...$tags,
): JsonResponse {
    // ...
}
```

Variadic parameters 会被描述为 arrays，并且不会标记为 required。

### Query Object

`#[MapQueryString]` 描述 query object。

```php
use Symfony\Component\HttpKernel\Attribute\MapQueryString;

public function list(#[MapQueryString] PetSearchQuery $query): JsonResponse
{
    // ...
}
```

未设置 `key` 时，参数名使用 PHP 参数名，对象使用 `style: form`。设置 `key` 时，该值成为参数名，对象使用 `style: deepObject`。

### Request Body

`#[MapRequestPayload]` 创建 OpenAPI `requestBody`。

```php
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;

public function create(#[MapRequestPayload(acceptFormat: 'json')] CreatePetRequest $request): PetView
{
    // ...
}
```

生成的 request body:

- PHP parameter type 会成为 request schema.
- `acceptFormat` 是可选的。如果省略，将使用 route default `_format`；如果 `_format` 也不存在，则使用 `json`.
- `acceptFormat` 从 Symfony request format 转换为 media type，例如 `json` 转为 `application/json`.
- 如果 PHP parameter 是 required，OpenAPI request body 也是 required.
- 对于 array payloads，`MapRequestPayload(type: SomeDto::class)` 描述 item type.

### Uploaded Files

`#[MapUploadedFile]` 添加带 binary fields 的 `multipart/form-data` request body。

```php
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpKernel\Attribute\MapUploadedFile;

public function upload(#[MapUploadedFile(name: 'photo')] UploadedFile $file): JsonResponse
{
    // ...
}
```

Variadic uploaded files 会被描述为 binary strings 的 array，并且不会标记为 required。

### Date And Time

`#[MapDateTime(format: ...)]` 修改 controller parameters 的 generated date/time example。

```php
use Symfony\Component\HttpKernel\Attribute\MapDateTime;

public function history(#[MapDateTime(format: 'Y-m-d')] DateTimeImmutable $date): JsonResponse
{
    // ...
}
```

`format` 参数是可选的。如果省略，将使用 default timestamp format。

## 响应

按公开 API 应该呈现的样子编写 controller return types。

| Controller return type | Generated response |
| --- | --- |
| View object, DTO, scalar, array | JSON response body。Schema 从方法 return type 读取。默认 status `200`. |
| 显式 `void` | Empty response。默认 status `204`. |
| Symfony `Response` subclass | Response body 不会自动生成。需要手写 response 文档时使用 `#[Operation]`. |

对于 JSON API，通常不需要 response format option。只有 defaults 不适合当前 endpoint 时才使用 route options:

- `response_code` 修改 documented status，例如 create actions 使用 `201`.
- `response_format` 文档化非默认 response format.
- `response_formats` 文档化多个 response formats.

```php
#[Route('/api/pets/{id}', methods: ['GET'])]
public function show(int $id): PetView
{
    // ...
}
```

Symfony 8.1 引入了 [`#[Serialize]`](https://symfony.com/blog/new-in-symfony-8-1-serialize-attribute)，用于在 runtime 序列化 controller results。当该属性存在时，本包会读取 `Serialize::code`；schema 仍然来自 PHP return type。

```php
use Symfony\Component\HttpKernel\Attribute\Serialize;

#[Route('/api/pets', methods: ['POST'], options: ['response_code' => 201])]
#[Serialize(code: 201)]
public function create(CreatePetRequest $request): PetView
{
    // ...
}
```

没有 response body 的 actions 使用显式 `void` return type:

```php
#[Route('/api/pets/{id}', methods: ['DELETE'])]
public function delete(int $id): void
{
    // ...
}
```

这会把 endpoint 文档化为空的 `204` response。Symfony 自身不会把 controller 的 `null` result 转成 `204`，因此使用 `void` actions 的应用需要在 runtime 处理这个行为。

如果应用还不能使用 Symfony 8.1，一个小的 `KernelEvents::VIEW` listener 可以同时处理两个场景：`null` 变成 `204`，其他 controller results 序列化为 JSON。Symfony 自身实现是 [`SerializeControllerResultAttributeListener`](https://github.com/symfony/http-kernel/blob/ad1426284c2e7fe10de65dc68a25a724639e3838/EventListener/SerializeControllerResultAttributeListener.php)；一个 JSON-only 的最小版本可以这样写:

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

如果项目对 response status 或 formats 有不同规则，可以替换 `ResponseMetadataResolverInterface`。

## OpenAPI Attributes

该包为 PHP types 不足以表达的场景提供少量 OpenAPI attributes:

| Attribute | Target | 用途 |
| --- | --- | --- |
| `#[Operation]` | class, method | 添加 manual OpenAPI operation fragment. |
| `#[ItemType]` | property, parameter | 描述 array item type. |
| `#[SchemaName]` | class | 覆盖 component schema name. |
| `#[PropertyName]` | property | 覆盖 OpenAPI property name. |
| `#[IgnoreProperty]` | property | 从 object schema 排除 property. |
| `#[TimestampFormat]` | property | 覆盖 date/time example format. |

Array item types 通常从 PHPDoc 读取:

```php
/** @var list<PetView> */
public array $pets;
```

支持 `PetView[]`、`list<PetView>`、`array<PetView>` 和 `array<string, PetView>`。支持 `array<PetView|null>` 这样的 nullable item type。`array<mixed>` 和 `array<PetView|ErrorView>` 这类过宽或不明确的 item type 会被忽略。需要显式覆盖或 item limit 时使用 `#[ItemType]`; 它优先于 `@var`。

## 手写 OpenAPI Fragments

大多数 endpoints 不需要手写 OpenAPI fragments。特殊场景可以使用 `#[Operation]`:

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

该 fragment 会合并到 generated operation。

### 记录错误响应

我们建议让 API 保持可预测：成功的 action 应返回一个已记录的 view object，错误应使用已记录的 error shape，而不是隐藏在 controller 分支里。

对于共享的 error response，可以用 `#[Operation]` 或 `openapi.initial_operation` 描述 `default` response：

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

在 YAML/PHP arrays 中，schema value 可以是 PHP type string。当字符串包含 `\` 时，它会被当作 PHP type。对于没有 namespace 的 class，请使用前导 backslash，例如 `\AppErrorView`。在 PHP attributes 中，如果需要显式 type object，请使用 `new Type(ErrorView::class)`。

## PHP Type Schema Resolvers

默认 schema generation 覆盖常见 PHP types:

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

如果项目有需要 custom schema 的 PHP type，请实现 `OpenApiPhpTypeSchemaResolverInterface`，并在 `OpenApiPhpTypeSchemaResolverManagerInterface` 中注册 resolver。

## Object Schemas

DTO 和 view objects 会根据 typed properties 生成 schema。

它直接读取 PHP classes:

- 支持 instantiable non-internal classes;
- 反射 public、protected 和 private properties;
- property types 会成为 OpenAPI property schemas;
- 没有 default value 的 properties 会标记为 required;
- scalar 和 backed enum default values 会写入 schema;
- 支持 constructor-promoted property defaults;
- `#[SchemaName]` 修改 component schema name;
- `#[PropertyName]` 修改 property name;
- `#[IgnoreProperty]` 排除 property;
- 能解析时，array item types 会从 `@var` 读取;
- `#[ItemType]` 显式描述 array properties，并且优先于 `@var`;
- `#[TimestampFormat]` 修改 date/time examples.

该 resolver 不使用 Symfony Serializer metadata。它不会读取 serializer groups、getters、setters、`SerializedName`、name converters 或 camelCase/snake_case conversion rules。

我们建议使用显式 DTO 和 View classes，并使用 typed properties。如果需要不同的外部结构，请创建新的 View object，并把 domain object 映射进去。这会让搜索、refactoring 和 schema generation 更简单。

如果你的团队需要 first-class Symfony Serializer support，请创建 issue。我们会考虑将其作为 optional resolver 或 strategy 添加。

Symfony Serializer 参考: [Serializer](https://symfony.com/doc/current/serializer.html).

## 扩展点

该包由可替换 services 组成，适合有自定义 conventions 的项目:

| Service/interface | 用途 |
| --- | --- |
| `RouteMetadataResolverInterface` | 控制 tags、summary、description、deprecation 和 API filtering. |
| `ResponseMetadataResolverInterface` | 控制 response status codes 和 response formats. |
| `OpenApiOperationEnricherInterface` | 添加 request parameters、request bodies、responses 或 custom operation data. |
| `OpenApiPhpTypeSchemaResolverInterface` | 将 PHP types 转换为 OpenAPI schemas. |
| `OpenApiPathBuilderInterface` | 将 Symfony route paths 转换为 OpenAPI paths. |

当项目规则与 defaults 不同，可以在 Symfony container 中替换这些 services。
