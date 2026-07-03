# OpenAPI Generator for Symfony Routing

[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/sunrise-studio-development/symfony-openapi/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/sunrise-studio-development/symfony-openapi/?branch=master)
[![Code Coverage](https://scrutinizer-ci.com/g/sunrise-studio-development/symfony-openapi/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/sunrise-studio-development/symfony-openapi/?branch=master)
[![Build Status](https://scrutinizer-ci.com/g/sunrise-studio-development/symfony-openapi/badges/build.png?b=master)](https://scrutinizer-ci.com/g/sunrise-studio-development/symfony-openapi/build-status/master)
[![Code Intelligence Status](https://scrutinizer-ci.com/g/sunrise-studio-development/symfony-openapi/badges/code-intelligence.svg?b=master)](https://scrutinizer-ci.com/code-intelligence)

Idiomas: [🇬🇧 English](README.md) | [🇨🇳 简体中文](README-zh-CN.md) | [🇪🇸 Español](README-es.md) | [🇵🇹 Português](README-pt.md) | [🇷🇺 Русский](README-ru.md) | [🇺🇦 Українська](README-uk.md)

Este pacote gera um documento OpenAPI a partir de rotas Symfony, assinaturas de controladores, atributos Symfony HttpKernel e classes DTO/View tipadas.

O objetivo é manter a documentação da API próxima do código da aplicação. Endpoints normais não devem exigir grandes blocos `#[OA\...]`. Rotas descrevem paths e methods, atributos Symfony descrevem request mapping, DTOs descrevem dados de entrada, view objects descrevem dados de saída e route options descrevem operation metadata. Fragmentos OpenAPI manuais continuam disponíveis para casos excepcionais.

A API pública fica no namespace `Sunrise\Symfony\OpenApi`.

Leituras adicionais:

- [Artigo no Medium](https://medium.com/@a.fenric/openapi-without-oa-how-i-built-a-symfony-documentation-generator-af2ff83bd21c) explica qual problema este pacote resolve e como usá-lo em uma aplicação Symfony.
- [PHP Annotations plugin para PhpStorm](https://plugins.jetbrains.com/plugin/7320-php-annotations) deve suportar aliases para os attributes deste pacote nas próximas versões, o que pode melhorar IDE completion e navigation.

## Instalação

```bash
composer require sunrise-studio/symfony-openapi
```

O pacote requer PHP 8.2 ou mais novo. As versões suportadas dos componentes Symfony estão definidas em `composer.json`. Symfony 8.1 ou mais novo só é necessário se a aplicação quiser usar o atributo runtime nativo `#[Serialize]`.

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

Isso importa duas rotas de documentação:

| Route | Controller | Propósito |
| --- | --- | --- |
| `GET /docs` | `SwaggerController` | Serve Swagger UI configurado para ler `/docs/openapi.json`. |
| `GET /docs/openapi.json` | `DocumentController` | Serve o documento OpenAPI JSON gerado. |

Essas rotas não entram no API document gerado porque elas não definem `api: true`, e seus paths não começam com `/api/`.

Se você precisa de apenas uma rota, importe o arquivo dela diretamente:

```yaml
openapi_document:
  resource: '@OpenApiBundle/config/routes/document.php'

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
| `openapi.document_uri` | `/docs/openapi.json` | Public URI do documento gerado. Swagger UI usa esse URI para carregar o documento. |
| `openapi.default_timestamp_format` | `OpenApiConfiguration::DEFAULT_TIMESTAMP_FORMAT` | Formato PHP `date()` usado para gerar valores OpenAPI `example` para schemas de data/hora. |

`SwaggerConfiguration` pode ser substituído como serviço se você precisar de Swagger UI assets ou template variables.

### Custom Route Paths

Se apenas o Swagger UI precisa de outro path, defina a rota manualmente:

```yaml
# config/routes.yaml
swagger_ui:
  path: /swagger.html
  controller: Sunrise\Symfony\OpenApi\Controller\SwaggerController
  methods: [GET]
  options:
    api: false
```

Se a route do OpenAPI document também mudar, atualize a rota e `openapi.document_uri` para que o Swagger UI carregue o documento correto:

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

## Gerar O Documento

Execute:

```bash
php bin/console openapi:build-document
```

O comando lê a coleção de rotas, mantém as routes que devem ser documentadas, constrói o OpenAPI document e escreve em `openapi.document_filename`.

Depois da geração, com as rotas padrão do pacote importadas:

- `/docs` abre Swagger UI.
- `/docs/openapi.json` retorna o JSON document gerado.

## Route Options

Route options são o lugar padrão para operation metadata:

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

Options suportadas:

| Option | Type | Propósito |
| --- | --- | --- |
| `tag`, `tags` | `string\|string[]` | OpenAPI operation tags. |
| `summary` | `string` | OpenAPI operation summary. |
| `description` | `string` | OpenAPI operation description. |
| `deprecated`, `is_deprecated`, `isDeprecated` | `bool` | Marca uma operation como deprecated. |
| `api`, `is_api`, `isApi` | `bool` | Inclui ou exclui a route do generated document. |
| `response_code` | `int` | Documented response status quando `#[Serialize]` não fornece um code. Defaults: `200` para response body e `204` para `void` explícito. |
| `response_format` | `string` | Response format para o documented response body, convertido para media type, por exemplo `json` para `application/json`. |
| `response_formats` | `string[]` | Vários response formats. Ignorado quando `response_format` está definido. |

Se nenhuma API option for definida, routes cujo path começa com `/api/` são tratadas como API routes.

Se o seu projeto não quer manter tags, summaries, descriptions e API filtering em route options, substitua `RouteMetadataResolverInterface`.

## Symfony Attributes

O pacote entende os Symfony controller attributes que descrevem request data. Veja a [documentação do Symfony](https://symfony.com/doc/current/controller/value_resolver.html).

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

Sem `key`, o nome do parâmetro é o nome do parâmetro PHP e o objeto usa `style: form`. Com `key`, esse valor vira o nome do parâmetro e o objeto usa `style: deepObject`.

> [!NOTE]
> Versões do Symfony anteriores a 7.3 não contêm o argumento `MapQueryString::key`. Veja o [Symfony 7.3 HttpKernel changelog](https://github.com/symfony/symfony/blob/7.3/src/Symfony/Component/HttpKernel/CHANGELOG.md) e as [Symfony 7.3 DX improvements](https://symfony.com/blog/new-in-symfony-7-3-dx-improvements-part-2#improved-mapquerystring).

### Request Body

`#[MapRequestPayload]` cria um OpenAPI `requestBody`.

```php
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;

public function create(#[MapRequestPayload(acceptFormat: 'json')] CreatePetRequest $request): PetView
{
    // ...
}
```

Request body gerado:

- O PHP parameter type vira o request schema.
- `acceptFormat` é opcional. Se omitido, o route default `_format` é usado; se `_format` também estiver ausente, `json` é usado.
- `acceptFormat` é convertido de Symfony request format para media type, por exemplo `json` para `application/json`.
- Se o PHP parameter é required, o OpenAPI request body também é required.
- Para array payloads, `MapRequestPayload(type: SomeDto::class)` descreve o item type.

> [!NOTE]
> Versões do Symfony anteriores a 7.1 não contêm o argumento `MapRequestPayload::type`. Veja o [Symfony 7.1 HttpKernel changelog](https://github.com/symfony/symfony/blob/7.1/src/Symfony/Component/HttpKernel/CHANGELOG.md).

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

> [!NOTE]
> Versões do Symfony anteriores a 7.1 não contêm o atributo `MapUploadedFile`. Veja o [Symfony 7.1 HttpKernel changelog](https://github.com/symfony/symfony/blob/7.1/src/Symfony/Component/HttpKernel/CHANGELOG.md) e o [anúncio do Symfony 7.1 MapUploadedFile](https://symfony.com/blog/new-in-symfony-7-1-mapuploadedfile-attribute).

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

Escreva os controller return types como a API pública deve parecer.

| Return type do controller | Generated response |
| --- | --- |
| View object, DTO, scalar, array | JSON response body. A schema é lida do return type do método. Status padrão `200`. |
| `void` explícito | Empty response. Status padrão `204`. |
| Symfony `Response` subclass | Response body não é gerado automaticamente. Use `#[Operation]` se a response precisa ser documentada manualmente. |

Para APIs JSON, nenhuma response format option é necessária. Use route options apenas quando os defaults não combinarem com o endpoint:

- `response_code` altera o documented status, por exemplo `201` para create actions.
- `response_format` documenta um response format não padrão.
- `response_formats` documenta vários response formats.

```php
#[Route('/api/pets/{id}', methods: ['GET'])]
public function show(int $id): PetView
{
    // ...
}
```

Symfony 8.1 introduziu [`#[Serialize]`](https://symfony.com/blog/new-in-symfony-8-1-serialize-attribute), que serializa controller results em runtime. Quando esse atributo está presente, o pacote lê `Serialize::code`; a schema continua vindo do PHP return type.

```php
use Symfony\Component\HttpKernel\Attribute\Serialize;

#[Route('/api/pets', methods: ['POST'], options: ['response_code' => 201])]
#[Serialize(code: 201)]
public function create(CreatePetRequest $request): PetView
{
    // ...
}
```

Use um return type explícito `void` para actions sem response body:

```php
#[Route('/api/pets/{id}', methods: ['DELETE'])]
public function delete(int $id): void
{
    // ...
}
```

Isso documenta o endpoint como uma response vazia `204`. O Symfony não converte um resultado `null` do controller em `204` por conta própria, então aplicações que usam actions `void` devem tratar isso em runtime.

Se a aplicação ainda não puder usar Symfony 8.1, um pequeno listener para `KernelEvents::VIEW` pode cobrir os dois casos: `null` vira `204`, e outros controller results são serializados como JSON. A implementação do Symfony é [`SerializeControllerResultAttributeListener`](https://github.com/symfony/http-kernel/blob/ad1426284c2e7fe10de65dc68a25a724639e3838/EventListener/SerializeControllerResultAttributeListener.php); uma versão mínima JSON-only pode ser assim:

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

Se o seu projeto tem regras diferentes para response status ou formats, substitua `ResponseMetadataResolverInterface`.

## OpenAPI Attributes

O pacote fornece pequenos OpenAPI attributes para casos em que PHP types não são suficientes:

| Attribute | Target | Propósito |
| --- | --- | --- |
| `#[Operation]` | class, method | Adiciona um manual OpenAPI operation fragment. |
| `#[ItemType]` | property, parameter | Descreve array item type. |
| `#[SchemaName]` | class | Sobrescreve component schema name. |
| `#[PropertyName]` | property | Sobrescreve OpenAPI property name. |
| `#[IgnoreProperty]` | property | Exclui uma property do object schema. |
| `#[TimestampFormat]` | property | Sobrescreve date/time example format. |

Array item types normalmente são lidos do PHPDoc:

```php
/** @var list<PetView> */
public array $pets;
```

São suportados `PetView[]`, `list<PetView>`, `array<PetView>` e `array<string, PetView>`. Nullable item types como `array<PetView|null>` também são suportados. Item types amplos ou ambíguos, como `array<mixed>` e `array<PetView|ErrorView>`, são ignorados. Use `#[ItemType]` quando precisar de uma sobrescrita explícita ou de um item limit; ele tem prioridade sobre `@var`.

## Fragmentos OpenAPI Manuais

A maioria dos endpoints não deve precisar de OpenAPI fragments manuais. Para casos excepcionais, use `#[Operation]`:

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

O fragment é mesclado com a generated operation.

### Documentando Erros

Recomendamos manter a API previsível: uma action bem-sucedida deve retornar um view object documentado, e erros devem usar uma error shape documentada em vez de ficarem escondidos em branches do controller.

Para um error response comum, descreva um `default` response com `#[Operation]` ou com `openapi.initial_operation`:

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

Em arrays YAML/PHP, o valor de schema pode ser um PHP type string. Ele é tratado como PHP type quando a string contém `\`. Para uma classe sem namespace, use um backslash inicial, por exemplo `\AppErrorView`. Em PHP attributes, use `new Type(ErrorView::class)` quando precisar de um type object explícito.

## PHP Type Schema Resolvers

A geração padrão de schemas cobre PHP types comuns:

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

Se o seu projeto tem um PHP type custom que precisa de um custom schema, implemente `OpenApiPhpTypeSchemaResolverInterface` e registre o resolver em `OpenApiPhpTypeSchemaResolverManagerInterface`.

## Object Schemas

DTOs e view objects são descritos a partir de typed properties.

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
- array item types são lidos do `@var` quando possível;
- `#[ItemType]` descreve array properties explicitamente e tem prioridade sobre `@var`;
- `#[TimestampFormat]` altera date/time examples.

Este resolver não usa Symfony Serializer metadata. Ele não lê serializer groups, getters, setters, `SerializedName`, name converters ou regras de conversão camelCase/snake_case.

Recomendamos DTO e View classes explícitas com typed properties. Se você precisa de outra forma externa, crie um novo View object e mapeie seu domain object para ele. Isso mantém simples a busca, o refactoring e a schema generation.

Se a sua equipe precisa de first-class Symfony Serializer support, abra uma issue. Vamos considerar adicioná-lo como optional resolver ou strategy.

Referência Symfony Serializer: [Serializer](https://symfony.com/doc/current/serializer.html).

## Pontos De Extensão

O pacote é formado por serviços substituíveis para projetos com convenções próprias:

| Service/interface | Propósito |
| --- | --- |
| `RouteMetadataResolverInterface` | Controla tags, summary, description, deprecation e API filtering. |
| `ResponseMetadataResolverInterface` | Controla response status codes e response formats. |
| `OpenApiOperationEnricherInterface` | Adiciona request parameters, request bodies, responses ou custom operation data. |
| `OpenApiPhpTypeSchemaResolverInterface` | Converte PHP types para OpenAPI schemas. |
| `OpenApiPathBuilderInterface` | Converte Symfony route paths para OpenAPI paths. |

Substitua esses serviços no Symfony container quando as regras do projeto forem diferentes dos defaults.
