# OpenAPI Generator for Symfony Routing

![PHP](https://img.shields.io/packagist/dependency-v/sunrise-studio/symfony-openapi/php?style=social&logo=php&label=PHP)
![Coverage](https://img.shields.io/scrutinizer/coverage/g/sunrise-studio-development/symfony-openapi?style=social)
![Code quality](https://img.shields.io/scrutinizer/quality/g/sunrise-studio-development/symfony-openapi?style=social)

Sprachen: [English](README.md) | [Русский](README-ru.md) | [Українська](README-uk.md) | [Français](README-fr.md) | [Deutsch](README-de.md)

`sunrise-studio/symfony-openapi` generiert ein OpenAPI-Dokument aus Symfony-Routen, Controller-Signaturen, Symfony HTTP Kernel Attributen und PHP DTO/View Klassen.

Unser Ziel ist einfach: Anwendungsentwickler sollen für normale API endpoints keine großen OpenAPI-Attributblöcke schreiben müssen. Wir glauben, dass API-Dokumentation dem Code folgen sollte, der die API bereits beschreibt: Routen, input DTOs, query objects, uploaded files, path variables und response view objects. Manuelle OpenAPI-Fragmente sollten nur für Ausnahmefälle nötig sein.

Das Paket baut auf den OpenAPI-Mechanismen von [Sunrise HTTP Router](https://github.com/sunrise-php/http-router) auf, aber die Symfony-facing API liegt im Namespace `Sunrise\Symfony\OpenApi`.

## Installation

```bash
composer require sunrise-studio/symfony-openapi
```

Bundle registrieren:

```php
// config/bundles.php
return [
    Symfony\Bundle\FrameworkBundle\FrameworkBundle::class => ['all' => true],
    Sunrise\Symfony\OpenApi\OpenApiBundle::class => ['all' => true],
];
```

Paket-Routen importieren:

```yaml
# config/routes.yaml
openapi:
  resource: '@OpenApiBundle/config/routes.php'
```

Dadurch werden zwei Routen importiert:

| Route | Controller | Zweck |
| --- | --- | --- |
| `GET /openapi` | `OpenApiController` | Liefert das generierte OpenAPI JSON Dokument. |
| `GET /swagger.html` | `SwaggerController` | Liefert Swagger UI, konfiguriert für `/openapi`. |

Beide Paket-Routen sind mit `api: false` registriert und erscheinen deshalb nicht im generierten API-Dokument.

Wenn Sie nur eine Route wollen, importieren Sie die jeweilige Route-Datei direkt:

```yaml
openapi_document:
  resource: '@OpenApiBundle/config/routes/openapi.php'

swagger_ui:
  resource: '@OpenApiBundle/config/routes/swagger.php'
```

Symfony-Referenzen:

- [Routing](https://symfony.com/doc/current/routing.html)
- [Bundles](https://symfony.com/doc/current/bundles.html)

## Basis-Konfiguration

Eine typische Anwendungskonfiguration sieht so aus:

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

Nützliche Parameter:

| Parameter | Default | Zweck |
| --- | --- | --- |
| `openapi.initial_document` | OpenAPI version + `API` title | Basisdokument, das mit generierten paths und schemas zusammengeführt wird. |
| `openapi.initial_operation` | `responses: []` | Basis-operation für jede Route. |
| `openapi.document_filename` | `%kernel.project_dir%/var/openapi.json` | Datei, in die der Command das generierte Dokument schreibt. |
| `openapi.default_timestamp_format` | `OpenApiConfiguration::DEFAULT_TIMESTAMP_FORMAT` | PHP-`date()`-Format für OpenAPI-`example`-Werte von Datum/Zeit-Schemas. |
| `openapi.default_empty_response_status` | `204` | Default status für Controller-Methoden mit `void`. |
| `openapi.default_response_status` | `200` | Default status für serialisierte return objects. |
| `openapi.default_response_formats` | `['json']` | Default Symfony response formats für serialisierte return objects. |

`SwaggerConfiguration` kann ebenfalls als Service ersetzt oder konfiguriert werden, wenn eigene Swagger UI assets, template variables oder eine andere OpenAPI URL nötig sind.

## Dokument Bauen

Ausführen:

```bash
php bin/console openapi:build-document
```

Der Command liest die Symfony-Routensammlung, löst route metadata auf, behält nur API routes, adaptiert sie für den Sunrise OpenAPI document builder und speichert das Dokument in `openapi.document_filename`.

Danach:

- `/openapi` liefert das generierte JSON Dokument.
- `/swagger.html` öffnet Swagger UI.

## Route Options

Route options sind der Standardweg, route-level metadata zu beschreiben:

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

Unterstützte route options:

| Option | Type | Zweck |
| --- | --- | --- |
| `tags` | `string|string[]` | OpenAPI operation tags. |
| `summary` | `string` | OpenAPI operation summary. |
| `description` | `string` | OpenAPI operation description. |
| `deprecated`, `is_deprecated`, `isDeprecated` | `bool` | Markiert eine operation als deprecated. |
| `api`, `is_api`, `isApi` | `bool` | Schließt die Route in das generierte Dokument ein oder aus. |
| `response_status` | `int` | Überschreibt den generierten response status. |
| `response_formats` | `string|string[]` | Symfony response formats, zum Beispiel `json` oder `xml`. |

Wenn keine API option gesetzt ist, gelten Routen mit einem path, der mit `/api/` beginnt, als API routes.

Wenn Sie tags, summaries, descriptions oder API filtering nicht in route options speichern wollen, ersetzen Sie `RouteMetadataResolverInterface` durch einen eigenen Service.

## Symfony Attributes

Das Paket unterstützt Symfony controller value resolver attributes. Siehe [Symfony-Dokumentation](https://symfony.com/doc/current/controller/value_resolver.html).

### Request Body

`#[MapRequestPayload]` erzeugt ein OpenAPI `requestBody`.

```php
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;

public function create(#[MapRequestPayload(acceptFormat: 'json')] CreatePetRequest $request): PetView
{
    // ...
}
```

Verhalten:

- Der PHP-Typ des Parameters wird zum request schema.
- `acceptFormat` wird über Symfony request format in einen media type umgewandelt, zum Beispiel `json` in `application/json`.
- Wenn der Parameter in PHP required ist, wird der OpenAPI request body als required markiert.
- Für array payloads beschreibt `MapRequestPayload(type: SomeDto::class)` den item type.

### Query Object

`#[MapQueryString]` beschreibt ein query object.

```php
use Symfony\Component\HttpKernel\Attribute\MapQueryString;

public function list(#[MapQueryString] PetSearchQuery $query): JsonResponse
{
    // ...
}
```

Wenn `key` `null` ist, wird das Objekt als gesamter query string mit `style: form` beschrieben. Wenn `key` gesetzt ist, nutzt der Parameter `style: deepObject`.

### Query Parameter

`#[MapQueryParameter]` beschreibt scalar, enum, date, UID oder array query parameters.

```php
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;

public function find(
    #[MapQueryParameter] PetStatus $status,
    #[MapQueryParameter] string ...$tags,
): JsonResponse {
    // ...
}
```

Variadic parameters werden als arrays beschrieben und nicht als required markiert.

### Uploaded Files

`#[MapUploadedFile]` fügt einen `multipart/form-data` request body mit binary fields hinzu.

```php
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpKernel\Attribute\MapUploadedFile;

public function upload(#[MapUploadedFile(name: 'photo')] UploadedFile $file): JsonResponse
{
    // ...
}
```

Variadic uploaded files werden als array von binary strings beschrieben und nicht als required markiert.

### Path Variables

Symfony path variables werden aus kompilierten Routen gelesen. Requirements werden in OpenAPI schema patterns umgewandelt.

```php
#[Route('/api/pets/{petId}', requirements: ['petId' => '\d+'])]
public function show(int $petId): PetView
{
    // ...
}
```

Unterstützte reflektierte Parametertypen für path variables:

- `bool`
- `int`
- `float`
- `string`
- `BackedEnum`
- `DateTimeInterface`
- `Symfony\Component\Uid\AbstractUid`

Symfony route mapping aliases werden für einfache mappings wie `['id' => 'petId']` unterstützt. Entity-style mappings wie `{id:pet.id}` werden bewusst nicht als object schemas beschrieben; die öffentliche path variable wird weiterhin als string dokumentiert, sofern kein unterstützter scalar parameter gefunden wird.

### Date And Time

`#[MapDateTime(format: ...)]` ändert das generierte date/time example für Controller-Parameter.

```php
use Symfony\Component\HttpKernel\Attribute\MapDateTime;

public function history(#[MapDateTime(format: 'Y-m-d')] DateTimeImmutable $date): JsonResponse
{
    // ...
}
```

## Response Generation

Das default response behavior ist bewusst klein und vorhersehbar:

| Controller return type | Generierte response |
| --- | --- |
| `void` | Leere response, default status `204`. |
| Symfony `Response` subclass | Kein automatischer response content. Nutzen Sie `#[Operation]` für manuelle Fälle. |
| Jeder andere named return type | Serialized response body, default status `200`, default format `json`. |

Beispiel:

```php
#[Route('/api/pets/{id}', options: ['response_status' => 200])]
public function show(int $id): PetView
{
    // ...
}
```

Wenn eine Route ein custom view object zurückgibt, wird der return type über das PHP type schema resolver system aufgelöst und als response schema verwendet.

Wenn Ihr Projekt responses wrapped, zum Beispiel `{data: ..., meta: ...}`, ersetzen Sie `ResponseMetadataResolverInterface` oder die response operation enrichers durch eigene Services.

## Manuelle OpenAPI-Fragmente

Die meisten endpoints sollten keine manuellen OpenAPI-Fragmente brauchen. Für Ausnahmefälle nutzen Sie `#[Operation]`:

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

Das Fragment wird durch Sunrise `OpenApiDocumentManager` in die generierte operation gemerged.

## Symfony OpenAPI Annotations

Das Paket stellt Symfony-facing annotations bereit, damit Anwendungscode für übliche OpenAPI schema tasks keine router- oder hydrator-namespaces importieren muss:

| Annotation | Target | Zweck |
| --- | --- | --- |
| `#[Operation]` | class, method | Fügt ein manuelles OpenAPI operation fragment hinzu. |
| `#[ItemType]` | property, parameter | Beschreibt den item type von arrays. |
| `#[SchemaName]` | class | Überschreibt den component schema name. |
| `#[PropertyName]` | property | Überschreibt den OpenAPI property name. |
| `#[IgnoreProperty]` | property | Schließt eine property aus dem object schema aus. |
| `#[TimestampFormat]` | property | Überschreibt das date/time example format. |

## PHP Type Schema Resolution

Das Bundle registriert die Sunrise schema resolvers explizit und ersetzt den timestamp resolver durch eine Symfony-aware Variante.

Aktive resolvers:

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

Wenn Ihr Projekt einen custom type mit eigenem schema braucht, implementieren Sie `Sunrise\Http\Router\OpenApi\OpenApiPhpTypeSchemaResolverInterface` und registrieren Sie den resolver im Service `OpenApiPhpTypeSchemaResolverManagerInterface`.

## Object Schema Resolver

`Sunrise\Http\Router\OpenApi\PhpTypeSchemaResolver\ObjectPhpTypeSchemaResolver` ist der wichtigste resolver für DTOs und view objects.

Er liest PHP-Klassen direkt:

- instantiable non-internal classes werden unterstützt;
- public/private/protected properties werden reflektiert;
- property types werden OpenAPI property schemas;
- properties ohne default value werden als required markiert;
- property default values werden ausgegeben, wenn sie scalar oder backed enum sind;
- constructor-promoted property defaults werden unterstützt;
- `#[SchemaName]` ändert den component schema name;
- `#[PropertyName]` ändert den property name;
- `#[IgnoreProperty]` schließt eine property aus;
- `#[ItemType]` beschreibt array properties;
- `#[TimestampFormat]` ändert date/time examples.

Dieser resolver nutzt keine Symfony Serializer metadata. Er liest keine serializer groups, getters, setters, `SerializedName`, name converters oder camelCase/snake_case conversion rules.

Wir empfehlen explizite DTO und View classes mit typisierten properties. Wenn Sie eine andere externe Form brauchen, erstellen Sie ein neues View object und mappen Sie Ihr Domain-Objekt darauf. Das hält Suche, Refactoring und Schema-Generierung einfach.

Wenn Ihr Team first-class Symfony Serializer Support braucht, öffnen Sie bitte ein Issue. Wir werden prüfen, ob wir ihn als optionalen resolver oder strategy layer hinzufügen.

Symfony Serializer Referenz: [Serializer](https://symfony.com/doc/current/serializer.html).

## Extension Points

Das Paket ist bewusst aus kleinen Services gebaut:

| Service/interface | Zweck |
| --- | --- |
| `RouteMetadataResolverInterface` | Steuert tags, summary, description, deprecation und API filtering. |
| `ResponseMetadataResolverInterface` | Steuert response status und response formats. |
| `OpenApiOperationEnricherInterface` | Fügt request parameters, request bodies, responses oder custom operation data hinzu. |
| `OpenApiPhpTypeSchemaResolverInterface` | Konvertiert PHP types zu OpenAPI schemas. |
| `OpenApiPathBuilderInterface` | Konvertiert Symfony route paths zu OpenAPI paths. |

Sie können diese Services im Symfony container ersetzen, wenn die Projektregeln von den defaults abweichen.

## Warum Dieses Paket Existiert

Wir haben viele APIs gesehen, die durch lange manuelle OpenAPI attribute blocks dokumentiert wurden. Das funktioniert, aber die Dokumentation wird oft zu einer zweiten Implementierung derselben API.

Wir wollen, dass der normale Weg anders ist:

- routes beschreiben paths und HTTP methods;
- Symfony attributes beschreiben request mapping;
- DTOs beschreiben input payloads;
- view objects beschreiben output payloads;
- route options beschreiben human operation metadata;
- OpenAPI-specific code wird nur genutzt, wenn das automatische Modell nicht reicht.

Je näher die Dokumentation am echten Anwendungscode liegt, desto schwerer driften beide auseinander.
