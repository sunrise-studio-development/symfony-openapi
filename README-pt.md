# OpenAPI Generator for Symfony Routing

[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/sunrise-studio-development/symfony-openapi/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/sunrise-studio-development/symfony-openapi/?branch=master)
[![Code Coverage](https://scrutinizer-ci.com/g/sunrise-studio-development/symfony-openapi/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/sunrise-studio-development/symfony-openapi/?branch=master)
[![Build Status](https://scrutinizer-ci.com/g/sunrise-studio-development/symfony-openapi/badges/build.png?b=master)](https://scrutinizer-ci.com/g/sunrise-studio-development/symfony-openapi/build-status/master)
[![Code Intelligence Status](https://scrutinizer-ci.com/g/sunrise-studio-development/symfony-openapi/badges/code-intelligence.svg?b=master)](https://scrutinizer-ci.com/code-intelligence)

Idiomas: [🇬🇧 English](README.md) | [🇨🇳 简体中文](README-zh-CN.md) | [🇪🇸 Español](README-es.md) | [🇵🇹 Português](README-pt.md) | [🇷🇺 Русский](README-ru.md) | [🇺🇦 Українська](README-uk.md)

Este pacote gera um documento OpenAPI a partir de rotas Symfony, assinaturas de controladores, atributos Symfony HttpKernel e classes DTO/View tipadas.

O objetivo é manter a documentação da API próxima do código da aplicação. Endpoints normais não devem exigir grandes blocos `#[OA\...]`. Rotas descrevem paths e methods, atributos Symfony descrevem request mapping, DTOs descrevem dados de entrada, view objects descrevem dados de saída e route options descrevem operation metadata. Fragmentos OpenAPI manuais continuam disponíveis para casos excepcionais.

A API fica no namespace `Sunrise\Symfony\OpenApi`. Internamente, o pacote usa o OpenAPI engine do [Sunrise HTTP Router](https://github.com/sunrise-php/http-router).

## Instalação

```bash
composer require sunrise-studio/symfony-openapi
```

Registre o bundle:

```php
// config/bundles.php
return [
    Symfony\Bundle\FrameworkBundle\FrameworkBundle::class => ['all' => true],
    Sunrise\Symfony\OpenApi\OpenApiBundle::class => ['all' => true],
];
```

Importe as rotas do pacote:

```yaml
# config/routes.yaml
openapi:
  resource: '@OpenApiBundle/config/routes.php'
```

Isso importa duas rotas:

| Route | Controller | Propósito |
| --- | --- | --- |
| `GET /openapi` | `OpenApiController` | Serve o documento OpenAPI JSON gerado. |
| `GET /swagger.html` | `SwaggerController` | Serve Swagger UI configurado para ler `/openapi`. |

Essas rotas não entram no API document gerado: elas não definem `api: true`, e seus paths não começam com `/api/`.

Se você precisa de apenas uma rota, importe o arquivo dela diretamente:

```yaml
openapi_document:
  resource: '@OpenApiBundle/config/routes/openapi.php'

swagger_ui:
  resource: '@OpenApiBundle/config/routes/swagger.php'
```

Referências Symfony:

- [Routing](https://symfony.com/doc/current/routing.html)
- [Bundles](https://symfony.com/doc/current/bundles.html)

## Configuração

Configuração típica de uma aplicação:

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

Parâmetros úteis:

| Parâmetro | Default | Propósito |
| --- | --- | --- |
| `openapi.initial_document` | OpenAPI version + `API` title | Documento base mesclado com os generated paths e schemas. |
| `openapi.initial_operation` | `responses: []` | Operation base mesclada com cada generated operation. |
| `openapi.document_filename` | `%kernel.project_dir%/var/openapi.json` | Output file usado por `openapi:build-document`. |
| `openapi.default_timestamp_format` | `OpenApiConfiguration::DEFAULT_TIMESTAMP_FORMAT` | Formato PHP `date()` usado para gerar valores OpenAPI `example` para schemas de data/hora. |
| `openapi.default_empty_response_status` | `204` | Status padrão para controller methods com `void`. |
| `openapi.default_response_status` | `200` | Status padrão para return objects serializáveis. |
| `openapi.default_response_formats` | `['json']` | Symfony response formats padrão para return objects serializáveis. |

`SwaggerConfiguration` pode ser substituído como serviço se você precisar de Swagger UI assets, template variables ou uma OpenAPI URL diferente.

## Gerar O Documento

Execute:

```bash
php bin/console openapi:build-document
```

O comando lê a coleção de rotas, resolve route metadata, mantém API routes, constrói o OpenAPI document e escreve em `openapi.document_filename`.

Depois da geração:

- `/openapi` retorna o JSON document gerado.
- `/swagger.html` abre Swagger UI.

## Route Options

Route options são a fonte padrão de route-level OpenAPI metadata:

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

Options suportadas:

| Option | Type | Propósito |
| --- | --- | --- |
| `tags` | `string\|string[]` | OpenAPI operation tags. |
| `summary` | `string` | OpenAPI operation summary. |
| `description` | `string` | OpenAPI operation description. |
| `deprecated`, `is_deprecated`, `isDeprecated` | `bool` | Marca uma operation como deprecated. |
| `api`, `is_api`, `isApi` | `bool` | Inclui ou exclui a route do generated document. |
| `response_status` | `int` | Sobrescreve o generated response status. |
| `response_formats` | `string\|string[]` | Symfony response formats, por exemplo `json` ou `xml`. |

Se nenhuma API option for definida, routes cujo path começa com `/api/` são tratadas como API routes.

Se route options não forem o lugar correto para metadata no seu projeto, substitua `RouteMetadataResolverInterface`.

## Symfony Attributes

O pacote suporta Symfony controller value resolver attributes. Veja a [documentação do Symfony](https://symfony.com/doc/current/controller/value_resolver.html).

### Path Variables

Symfony path variables são lidas de rotas compiladas. Requirements são convertidos para OpenAPI schema patterns.

```php
#[Route('/api/pets/{petId}', requirements: ['petId' => '\d+'])]
public function show(int $petId): PetView
{
    // ...
}
```

Reflected parameter types suportados para path variables:

- `bool`
- `int`
- `float`
- `string`
- `BackedEnum`
- `DateTimeInterface`
- `Symfony\Component\Uid\AbstractUid`

Symfony route mapping aliases são suportados para mappings simples como `['id' => 'petId']`. Entity-style mappings como `{id:pet.id}` não são descritos como object schemas; a path variable pública é documentada como string se nenhum scalar parameter suportado for encontrado.

### Query Parameter

`#[MapQueryParameter]` descreve scalar, enum, date/time, UID ou array query parameters.

```php
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;

public function find(
    #[MapQueryParameter] PetStatus $status,
    #[MapQueryParameter] string ...$tags,
): JsonResponse {
    // ...
}
```

Variadic parameters são descritos como arrays e não são marcados como required.

### Query Object

`#[MapQueryString]` descreve um query object.

```php
use Symfony\Component\HttpKernel\Attribute\MapQueryString;

public function list(#[MapQueryString] PetSearchQuery $query): JsonResponse
{
    // ...
}
```

Se `key` é `null`, o objeto é descrito como todo o query string com `style: form`. Se `key` é definido, o parâmetro usa `style: deepObject`.

### Request Body

`#[MapRequestPayload]` cria um OpenAPI `requestBody`.

```php
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;

public function create(#[MapRequestPayload(acceptFormat: 'json')] CreatePetRequest $request): PetView
{
    // ...
}
```

Comportamento:

- O PHP parameter type vira o request schema.
- `acceptFormat` é opcional. Se omitido, os default accept formats são usados; por padrão, é `json`.
- `acceptFormat` é convertido de Symfony request format para media type, por exemplo `json` para `application/json`.
- Se o PHP parameter é required, o OpenAPI request body também é required.
- Para array payloads, `MapRequestPayload(type: SomeDto::class)` descreve o item type.

### Uploaded Files

`#[MapUploadedFile]` adiciona um `multipart/form-data` request body com binary fields.

```php
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpKernel\Attribute\MapUploadedFile;

public function upload(#[MapUploadedFile(name: 'photo')] UploadedFile $file): JsonResponse
{
    // ...
}
```

Variadic uploaded files são descritos como array de binary strings e não são marcados como required.

### Date And Time

`#[MapDateTime(format: ...)]` altera o generated date/time example para controller parameters.

```php
use Symfony\Component\HttpKernel\Attribute\MapDateTime;

public function history(#[MapDateTime(format: 'Y-m-d')] DateTimeImmutable $date): JsonResponse
{
    // ...
}
```

O argumento `format` é opcional. Se omitido, o default timestamp format é usado.

## Geração De Respostas

A geração de responses padrão é intencionalmente limitada:

| Return type do controlador | Generated response |
| --- | --- |
| `void` | Empty response, default status `204`. |
| Symfony `Response` subclass | Automatic response content não é gerado. Use `#[Operation]` quando a response precisa ser documentada manualmente. |
| Any other named return type | Serialized response body, default status `200`, default format `json`. |

Exemplo:

```php
#[Route('/api/pets/{id}', options: ['response_status' => 200])]
public function show(int $id): PetView
{
    // ...
}
```

Se uma route retorna um custom view object, o return type é usado como response schema.

Se o seu projeto envolve responses, por exemplo `{data: ..., meta: ...}`, substitua `ResponseMetadataResolverInterface` ou os response operation enrichers.

## OpenAPI Attributes

O pacote fornece OpenAPI attributes para tarefas comuns de schema:

| Attribute | Target | Propósito |
| --- | --- | --- |
| `#[Operation]` | class, method | Adiciona um manual OpenAPI operation fragment. |
| `#[ItemType]` | property, parameter | Descreve array item type. |
| `#[SchemaName]` | class | Sobrescreve component schema name. |
| `#[PropertyName]` | property | Sobrescreve OpenAPI property name. |
| `#[IgnoreProperty]` | property | Exclui uma property do object schema. |
| `#[TimestampFormat]` | property | Sobrescreve date/time example format. |

## Fragmentos OpenAPI Manuais

A maioria dos endpoints não deve precisar de OpenAPI fragments manuais. Para casos excepcionais, use `#[Operation]`:

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

O fragment é mesclado com a generated operation.

## PHP Type Schema Resolvers

Resolvers registrados:

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

Se o seu projeto tem um PHP type custom que precisa de um custom schema, implemente `OpenApiPhpTypeSchemaResolverInterface` e registre o resolver no serviço `OpenApiPhpTypeSchemaResolverManagerInterface`.

## Object Schema Resolver

`ObjectPhpTypeSchemaResolver` é o resolver principal para DTOs e view objects.

Ele lê PHP classes diretamente:

- instantiable non-internal classes são suportadas;
- public, protected e private properties são refletidas;
- property types viram OpenAPI property schemas;
- properties sem default value são marcadas como required;
- scalar e backed enum default values são emitidos no schema;
- constructor-promoted property defaults são suportados;
- `#[SchemaName]` altera component schema name;
- `#[PropertyName]` altera property name;
- `#[IgnoreProperty]` exclui uma property;
- `#[ItemType]` descreve array properties;
- `#[TimestampFormat]` altera date/time examples.

Este resolver não usa Symfony Serializer metadata. Ele não lê serializer groups, getters, setters, `SerializedName`, name converters ou regras de conversão camelCase/snake_case.

Recomendamos DTO e View classes explícitas com typed properties. Se você precisa de outra forma externa, crie um novo View object e mapeie seu domain object para ele. Isso mantém simples a busca, o refactoring e a schema generation.

Se a sua equipe precisa de first-class Symfony Serializer support, abra uma issue. Vamos considerar adicioná-lo como optional resolver ou strategy.

Referência Symfony Serializer: [Serializer](https://symfony.com/doc/current/serializer.html).

## Pontos De Extensão

O pacote é formado por serviços substituíveis:

| Service/interface | Propósito |
| --- | --- |
| `RouteMetadataResolverInterface` | Controla tags, summary, description, deprecation e API filtering. |
| `ResponseMetadataResolverInterface` | Controla response status e response formats. |
| `OpenApiOperationEnricherInterface` | Adiciona request parameters, request bodies, responses ou custom operation data. |
| `OpenApiPhpTypeSchemaResolverInterface` | Converte PHP types para OpenAPI schemas. |
| `OpenApiPathBuilderInterface` | Converte Symfony route paths para OpenAPI paths. |

Substitua esses serviços no Symfony container quando as regras do projeto forem diferentes dos defaults.
