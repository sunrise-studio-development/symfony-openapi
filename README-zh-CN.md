# OpenAPI Generator for Symfony Routing

![PHP](https://img.shields.io/packagist/dependency-v/sunrise-studio/symfony-openapi/php?style=social&logo=php&label=PHP)
![Coverage](https://img.shields.io/scrutinizer/coverage/g/sunrise-studio-development/symfony-openapi?style=social)
![Code quality](https://img.shields.io/scrutinizer/quality/g/sunrise-studio-development/symfony-openapi?style=social)

语言: [🇬🇧 English](README.md) | [🇨🇳 简体中文](README-zh-CN.md) | [🇪🇸 Español](README-es.md) | [🇵🇹 Português](README-pt.md) | [🇷🇺 Русский](README-ru.md) | [🇺🇦 Українська](README-uk.md)

`sunrise-studio/symfony-openapi` 根据 Symfony 路由、控制器签名、Symfony HttpKernel attributes，以及带类型的 PHP DTO/View 类生成 OpenAPI 文档。

目标是让 API 文档尽量贴近应用代码。普通 endpoints 不应该需要大量 `#[OA\...]` 代码块。路由描述 paths 和 methods，Symfony attributes 描述 request mapping，DTO 描述输入数据，view objects 描述输出数据，route options 描述 operation metadata。手写 OpenAPI fragments 只用于特殊场景。

Symfony API 位于 `Sunrise\Symfony\OpenApi` namespace。包内部使用 [Sunrise HTTP Router](https://github.com/sunrise-php/http-router) 的 OpenAPI engine。

## 安装

```bash
composer require sunrise-studio/symfony-openapi
```

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
| `GET /openapi` | `OpenApiController` | 返回生成的 OpenAPI JSON document. |
| `GET /swagger.html` | `SwaggerController` | 返回配置为读取 `/openapi` 的 Swagger UI. |

这两个路由都使用 `api: false`，因此不会进入生成的 API document。

如果只需要其中一个路由，可以直接导入对应文件:

```yaml
openapi_document:
  resource: '@OpenApiBundle/config/routes/openapi.php'

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
    openapi: !php/const Sunrise\Http\Router\OpenApi\OpenApiConfiguration::VERSION
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
| `openapi.default_timestamp_format` | `OpenApiConfiguration::DEFAULT_TIMESTAMP_FORMAT` | 用于为 date/time schemas 生成 OpenAPI `example` 的 PHP `date()` 格式. |
| `openapi.default_empty_response_status` | `204` | `void` controller methods 的默认 status. |
| `openapi.default_response_status` | `200` | 可序列化 return objects 的默认 status. |
| `openapi.default_response_formats` | `['json']` | 可序列化 return objects 的默认 Symfony response formats. |

如果需要自定义 Swagger UI assets、template variables 或 OpenAPI URL，可以把 `SwaggerConfiguration` 替换为自己的 service。

## 生成文档

运行:

```bash
php bin/console openapi:build-document
```

该命令读取 Symfony route collection，解析 route metadata，保留 API routes，构建 OpenAPI document，并写入 `openapi.document_filename`。

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
        'response_status' => 200,
        'response_formats' => ['json'],
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
| `tags` | `string|string[]` | OpenAPI operation tags. |
| `summary` | `string` | OpenAPI operation summary. |
| `description` | `string` | OpenAPI operation description. |
| `deprecated`, `is_deprecated`, `isDeprecated` | `bool` | 将 operation 标记为 deprecated. |
| `api`, `is_api`, `isApi` | `bool` | 将 route 包含到 generated document 中或从中排除. |
| `response_status` | `int` | 覆盖 generated response status. |
| `response_formats` | `string|string[]` | Symfony response formats，例如 `json` 或 `xml`. |

如果没有设置 API option，path 以 `/api/` 开头的 routes 会被视为 API routes。

如果 route options 不适合作为项目中的 metadata 来源，可以替换 `RouteMetadataResolverInterface`。

## Symfony Attributes

该包支持 Symfony controller value resolver attributes。参见 Symfony 的 [controller value resolver documentation](https://symfony.com/doc/current/controller/value_resolver.html)。

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
- `acceptFormat` 从 Symfony request format 转换为 media type，例如 `json` 转为 `application/json`.
- 如果 PHP parameter 是 required，OpenAPI request body 也是 required.
- 对于 array payloads，`MapRequestPayload(type: SomeDto::class)` 描述 item type.

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

### Date And Time

`#[MapDateTime(format: ...)]` 修改 controller parameters 的 generated date/time example。

```php
use Symfony\Component\HttpKernel\Attribute\MapDateTime;

public function history(#[MapDateTime(format: 'Y-m-d')] DateTimeImmutable $date): JsonResponse
{
    // ...
}
```

## 响应生成

默认 response generation 保持很小:

| Controller return type | Generated response |
| --- | --- |
| `void` | Empty response, default status `204`. |
| Symfony `Response` subclass | 不生成 automatic response content。如果 response 必须手动描述，使用 `#[Operation]`. |
| Any other named return type | Serialized response body, default status `200`, default format `json`. |

示例:

```php
#[Route('/api/pets/{id}', options: ['response_status' => 200])]
public function show(int $id): PetView
{
    // ...
}
```

如果 route 返回 custom view object，return type 会作为 response schema。

如果项目会包装 responses，例如 `{data: ..., meta: ...}`，请替换 `ResponseMetadataResolverInterface` 或 response operation enrichers。

## Symfony OpenAPI Annotations

该包为常见 OpenAPI schema tasks 提供 Symfony-facing annotations:

| Annotation | Target | 用途 |
| --- | --- | --- |
| `#[Operation]` | class, method | 添加 manual OpenAPI operation fragment. |
| `#[ItemType]` | property, parameter | 描述 array item type. |
| `#[SchemaName]` | class | 覆盖 component schema name. |
| `#[PropertyName]` | property | 覆盖 OpenAPI property name. |
| `#[IgnoreProperty]` | property | 从 object schema 排除 property. |
| `#[TimestampFormat]` | property | 覆盖 date/time example format. |

## 手写 OpenAPI Fragments

大多数 endpoints 不需要手写 OpenAPI fragments。特殊场景可以使用 `#[Operation]`:

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

该 fragment 会合并到 generated operation。

## PHP Type Schema Resolution

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
| `ResponseMetadataResolverInterface` | 控制 response status 和 response formats. |
| `OpenApiOperationEnricherInterface` | 添加 request parameters、request bodies、responses 或 custom operation data. |
| `OpenApiPhpTypeSchemaResolverInterface` | 将 PHP types 转换为 OpenAPI schemas. |
| `OpenApiPathBuilderInterface` | 将 Symfony route paths 转换为 OpenAPI paths. |

当项目规则与 defaults 不同，可以在 Symfony container 中替换这些 services。
