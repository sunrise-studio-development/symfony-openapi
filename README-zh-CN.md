# OpenAPI Generator for Symfony Routing

[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/sunrise-studio-development/symfony-openapi/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/sunrise-studio-development/symfony-openapi/?branch=master)
[![Code Coverage](https://scrutinizer-ci.com/g/sunrise-studio-development/symfony-openapi/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/sunrise-studio-development/symfony-openapi/?branch=master)
[![Build Status](https://scrutinizer-ci.com/g/sunrise-studio-development/symfony-openapi/badges/build.png?b=master)](https://scrutinizer-ci.com/g/sunrise-studio-development/symfony-openapi/build-status/master)
[![Code Intelligence Status](https://scrutinizer-ci.com/g/sunrise-studio-development/symfony-openapi/badges/code-intelligence.svg?b=master)](https://scrutinizer-ci.com/code-intelligence)

语言: [🇬🇧 English](README.md) | [🇨🇳 简体中文](README-zh-CN.md) | [🇪🇸 Español](README-es.md) | [🇵🇹 Português](README-pt.md) | [🇷🇺 Русский](README-ru.md) | [🇺🇦 Українська](README-uk.md)

此包根据 Symfony 路由、控制器签名、Symfony HttpKernel attributes，以及带类型的 DTO/View 类生成 OpenAPI 文档。

目标是让 API 文档尽量贴近应用代码。普通 endpoints 不应该需要大量 `#[OA\...]` 代码块。路由描述 paths 和 methods，Symfony attributes 描述 request mapping，DTO 描述输入数据，view objects 描述输出数据，route options 描述 operation metadata。手写 OpenAPI fragments 只用于特殊场景。

API 位于 `Sunrise\Symfony\OpenApi` namespace。包内部使用 [Sunrise HTTP Router](https://github.com/sunrise-php/http-router) 的 OpenAPI engine。

## 安装

```bash
composer require sunrise-studio/symfony-openapi
```

该包需要 Symfony HttpKernel 8.1 或更新版本。

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

这会导入两个路由:

| Route | Controller | 用途 |
| --- | --- | --- |
| `GET /openapi` | `DocumentController` | 返回生成的 OpenAPI JSON document. |
| `GET /swagger.html` | `SwaggerController` | 返回配置为读取 `/openapi` 的 Swagger UI. |

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
| `openapi.document_uri` | `/openapi` | 生成文档的 public URI。Swagger UI 使用它加载文档。 |
| `openapi.default_timestamp_format` | `OpenApiConfiguration::DEFAULT_TIMESTAMP_FORMAT` | 用于为 date/time schemas 生成 OpenAPI `example` 的 PHP `date()` 格式. |
| `openapi.default_empty_response_status_code` | `204` | 为显式 `void` return type 的 controller methods 生成的 status code. |

如果需要自定义 Swagger UI assets 或 template variables，可以把 `SwaggerConfiguration` 替换为自己的 service。

### Custom Route Paths

如果只有 Swagger UI 需要不同 path，可以自己定义 route:

```yaml
# config/routes.yaml
swagger_ui:
  path: /docs
  controller: Sunrise\Symfony\OpenApi\Controller\SwaggerController
  methods: [GET]
  options:
    api: false
```

如果 OpenAPI document route 也改变，需要同时更新 route 和 `openapi.document_uri`，这样 Swagger UI 才会加载正确文档:

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

## 生成文档

运行:

```bash
php bin/console openapi:build-document
```

该命令读取 route collection，解析 route metadata，保留 API routes，构建 OpenAPI document，并写入 `openapi.document_filename`。

生成后:

- `/openapi` 返回生成的 JSON document.
- `/swagger.html` 打开 Swagger UI.

## Route Options

Route options 是 route-level OpenAPI metadata 的默认来源:

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
| `tags` | `string\|string[]` | OpenAPI operation tags. |
| `summary` | `string` | OpenAPI operation summary. |
| `description` | `string` | OpenAPI operation description. |
| `deprecated`, `is_deprecated`, `isDeprecated` | `bool` | 将 operation 标记为 deprecated. |
| `api`, `is_api`, `isApi` | `bool` | 将 route 包含到 generated document 中或从中排除. |

如果没有设置 API option，path 以 `/api/` 开头的 routes 会被视为 API routes。

如果 route options 不适合作为项目中的 metadata 来源，可以替换 `RouteMetadataResolverInterface`。

## Symfony Attributes

该包支持 Symfony controller value resolver attributes。参见 Symfony 的 [controller value resolver documentation](https://symfony.com/doc/current/controller/value_resolver.html)。

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

如果 `key` 为 `null`，对象会被描述为整个 query string，使用 `style: form`。如果设置了 `key`，参数使用 `style: deepObject`。

### Request Body

`#[MapRequestPayload]` 创建 OpenAPI `requestBody`。

```php
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;

public function create(#[MapRequestPayload(acceptFormat: 'json')] CreatePetRequest $request): PetView
{
    // ...
}
```

行为:

- PHP parameter type 会成为 request schema.
- `acceptFormat` 是可选的。如果省略，将使用 default accept formats；默认是 `json`.
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

## 响应生成

该包只在控制器明确描述返回方式时生成 response 文档:

| Controller metadata | Generated response |
| --- | --- |
| `#[Serialize]` | Serialized response body。Status 来自 `Serialize::code`; schema 来自方法 return type. |
| 显式 `void` return type | Empty response。默认 status 是 `204`，可通过 `openapi.default_empty_response_status_code` 配置. |
| `#[EmptyResponse]` | 为 custom status 或 description 手动 override empty response. |
| 没有 OpenAPI attributes 的 Symfony `Response` subclass | 不生成 automatic response content。如果需要描述 response，使用 `#[Operation]` 或 `#[EmptyResponse]`. |

Serialized responses 使用 route default `_format` 作为 Symfony response format。如果没有设置 `_format`，使用 `json`。Format 通过 `Request::getMimeTypes()` 转为 media type.

```php
use Symfony\Component\HttpKernel\Attribute\Serialize;

#[Route('/api/pets/{id}', format: 'json')]
#[Serialize(code: 200)]
public function show(int $id): PetView
{
    // ...
}
```

如果 route 返回 custom view object，`#[Serialize]` 会把 return type 作为 response schema。

没有 response body 的 actions 使用显式 `void` return type:

```php
#[Route('/api/pets/{id}', methods: ['DELETE'])]
public function delete(int $id): void
{
    // ...
}
```

这只负责文档生成。Symfony 自身不会把 controller 的 `null` result 转成 `204`。在应用中，建议添加一个很小的 `KernelEvents::VIEW` listener，把 `null` result 转成 `new Response(status: 204)`。这个 runtime 行为不属于本包职责，它更适合由 Symfony 像 `SerializeControllerResultAttributeListener` 那样提供。

作为不那么 domain-oriented 的替代方案，可以手动返回 `new Response(status: 204)`，并在需要描述 custom empty response 时使用 `#[EmptyResponse]`:

```php
use Symfony\Component\HttpFoundation\Response;
use Sunrise\Symfony\OpenApi\Annotation\EmptyResponse;

#[Route('/api/jobs/{id}', methods: ['POST'])]
#[EmptyResponse(202, 'The job was accepted.')]
public function accept(int $id): Response
{
    return new Response(status: 202);
}
```

对于 `{data: ..., meta: ...}` 这样的 wrappers，可以添加 custom operation enricher，或用 `#[Operation]` 描述 response。

## OpenAPI Attributes

该包为常见 schema tasks 提供 OpenAPI attributes:

| Attribute | Target | 用途 |
| --- | --- | --- |
| `#[Operation]` | class, method | 添加 manual OpenAPI operation fragment. |
| `#[EmptyResponse]` | class, method | 添加 empty OpenAPI response，默认 `204`. |
| `#[ItemType]` | property, parameter | 描述 array item type. |
| `#[SchemaName]` | class | 覆盖 component schema name. |
| `#[PropertyName]` | property | 覆盖 OpenAPI property name. |
| `#[IgnoreProperty]` | property | 从 object schema 排除 property. |
| `#[TimestampFormat]` | property | 覆盖 date/time example format. |

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

已注册的 resolvers:

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

如果项目有需要 custom schema 的 PHP type，请实现 `OpenApiPhpTypeSchemaResolverInterface`，并在 `OpenApiPhpTypeSchemaResolverManagerInterface` service 中注册 resolver。

## Object Schema Resolver

`ObjectPhpTypeSchemaResolver` 是 DTO 和 view objects 的主要 resolver。

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
- `#[ItemType]` 描述 array properties;
- `#[TimestampFormat]` 修改 date/time examples.

该 resolver 不使用 Symfony Serializer metadata。它不会读取 serializer groups、getters、setters、`SerializedName`、name converters 或 camelCase/snake_case conversion rules。

我们建议使用显式 DTO 和 View classes，并使用 typed properties。如果需要不同的外部结构，请创建新的 View object，并把 domain object 映射进去。这会让搜索、refactoring 和 schema generation 更简单。

如果你的团队需要 first-class Symfony Serializer support，请创建 issue。我们会考虑将其作为 optional resolver 或 strategy 添加。

Symfony Serializer 参考: [Serializer](https://symfony.com/doc/current/serializer.html).

## 扩展点

该包由可替换 services 组成:

| Service/interface | 用途 |
| --- | --- |
| `RouteMetadataResolverInterface` | 控制 tags、summary、description、deprecation 和 API filtering. |
| `OpenApiOperationEnricherInterface` | 添加 request parameters、request bodies、responses 或 custom operation data. |
| `OpenApiPhpTypeSchemaResolverInterface` | 将 PHP types 转换为 OpenAPI schemas. |
| `OpenApiPathBuilderInterface` | 将 Symfony route paths 转换为 OpenAPI paths. |

当项目规则与 defaults 不同，可以在 Symfony container 中替换这些 services。
