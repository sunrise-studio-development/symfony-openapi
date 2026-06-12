# OpenAPI Generator for Symfony Routing

![PHP](https://img.shields.io/packagist/dependency-v/sunrise-studio/symfony-openapi/php?style=social&logo=php&label=PHP)
![Coverage](https://img.shields.io/scrutinizer/coverage/g/sunrise-studio-development/symfony-openapi?style=social)
![Code quality](https://img.shields.io/scrutinizer/quality/g/sunrise-studio-development/symfony-openapi?style=social)

Sprachen: [English](README.md) | [Русский](README-ru.md) | [Українська](README-uk.md) | [Français](README-fr.md) | [Deutsch](README-de.md)

`sunrise-studio/symfony-openapi` generiert ein OpenAPI-Dokument aus Symfony routes, Controller-Signaturen, Symfony HTTP Kernel Attributen und PHP DTO/View Klassen.

Unser Ziel ist einfach: Entwickler sollen für normale API endpoints keine großen OpenAPI-Attributblöcke schreiben müssen. Die Dokumentation soll dem Code folgen, der die API bereits beschreibt: routes, input DTOs, query objects, uploaded files, path variables und response view objects.

Das Paket nutzt den OpenAPI-Kern von [Sunrise HTTP Router](https://github.com/sunrise-php/http-router), aber die Symfony API liegt im Namespace `Sunrise\Symfony\OpenApi`.

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

Routes importieren:

```yaml
# config/routes.yaml
openapi:
  resource: '@OpenApiBundle/config/routes.php'
```

Mitgelieferte Routes:

| Route | Controller | Zweck |
| --- | --- | --- |
| `GET /openapi` | `OpenApiController` | Liefert das generierte OpenAPI JSON. |
| `GET /swagger.html` | `SwaggerController` | Liefert Swagger UI für `/openapi`. |

Beide Routes sind mit `api: false` registriert und erscheinen nicht in der generierten API-Dokumentation.

Einzelimport:

```yaml
openapi_document:
  resource: '@OpenApiBundle/config/routes/openapi.php'

swagger_ui:
  resource: '@OpenApiBundle/config/routes/swagger.php'
```

Symfony-Dokumentation: [Routing](https://symfony.com/doc/current/routing.html), [Bundles](https://symfony.com/doc/current/bundles.html).

## Konfiguration

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

Wichtige Parameter:

| Parameter | Default | Zweck |
| --- | --- | --- |
| `openapi.initial_document` | OpenAPI version + `API` title | Basisdokument. |
| `openapi.initial_operation` | `responses: []` | Basisoperation. |
| `openapi.document_filename` | `%kernel.project_dir%/var/openapi.json` | Ausgabedatei. |
| `openapi.default_timestamp_format` | Sunrise default | Format für Date examples. |
| `openapi.default_empty_response_status` | `204` | Status für `void`. |
| `openapi.default_response_status` | `200` | Status für serialisierte Objekte. |
| `openapi.default_response_formats` | `['json']` | Default response formats. |

`SwaggerConfiguration` kann als Service ersetzt werden, wenn andere Assets, Template-Variablen oder eine andere OpenAPI URL nötig sind.

## Command

```bash
php bin/console openapi:build-document
```

Der Command liest `RouterInterface`, filtert API routes, baut das Dokument und speichert es in `openapi.document_filename`.

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

Unterstützte Options:

- `tags`
- `summary`
- `description`
- `deprecated`, `is_deprecated`, `isDeprecated`
- `api`, `is_api`, `isApi`
- `response_status`
- `response_formats`

Wenn keine API option gesetzt ist, gilt ein Pfad mit `/api/` als API route. Für eine andere Regel ersetzen Sie `RouteMetadataResolverInterface`.

## Symfony Attributes

Das Paket unterstützt Symfony value resolver attributes: [Symfony docs](https://symfony.com/doc/current/controller/value_resolver.html).

- `#[MapRequestPayload]` erzeugt ein `requestBody`.
- `#[MapQueryString]` beschreibt ein query object.
- `#[MapQueryParameter]` beschreibt query parameters; variadic wird als array beschrieben und ist nicht required.
- `#[MapUploadedFile]` beschreibt `multipart/form-data`; variadic files werden arrays von binary strings und sind nicht required.
- Path variables kommen aus Symfony routes; requirements werden OpenAPI patterns.
- `#[MapDateTime(format: ...)]` ändert das timestamp example.

## Responses

| Return type | Response |
| --- | --- |
| `void` | Leere Response, default `204`. |
| Symfony `Response` subclass | Kein automatischer content. Nutzen Sie `#[Operation]`. |
| Anderer named return type | Serialisierter body, default `200`, default `json`. |

`response_status` und `response_formats` überschreiben defaults. Für eigene Policies ersetzen Sie `ResponseMetadataResolverInterface` oder die response enrichers.

## Annotations

| Annotation | Zweck |
| --- | --- |
| `#[Operation]` | Manueller OpenAPI operation fragment. |
| `#[ItemType]` | Item type für arrays. |
| `#[SchemaName]` | Component schema name. |
| `#[PropertyName]` | OpenAPI property name. |
| `#[IgnoreProperty]` | Property ausschließen. |
| `#[TimestampFormat]` | Timestamp example format. |

## PHP Type Schema Resolution

Aktive resolvers: bool, int, float, string, array, ArrayAccess collections, backed enums, objects, Symfony UID/UUID, timestamp.

Für eigene Typen implementieren Sie `Sunrise\Http\Router\OpenApi\OpenApiPhpTypeSchemaResolverInterface` und registrieren den resolver im Container.

## Object Schema Resolver

`ObjectPhpTypeSchemaResolver` liest PHP Klassen direkt: properties, types, defaults, required fields, promoted constructor defaults und schema annotations.

Symfony Serializer metadata wird nicht genutzt. Groups, getters, setters, `SerializedName`, name converters und camelCase/snake_case rules werden nicht gelesen.

Wir empfehlen explizite DTO/View Klassen mit typisierten Properties. Wenn Symfony Serializer Support gebraucht wird, öffnen Sie bitte eine issue.

Symfony Serializer Dokumentation: [Serializer](https://symfony.com/doc/current/serializer.html).

## Extension Points

- `RouteMetadataResolverInterface`
- `ResponseMetadataResolverInterface`
- `OpenApiOperationEnricherInterface`
- `OpenApiPhpTypeSchemaResolverInterface`
- `OpenApiPathBuilderInterface`

Diese Services können im Symfony container ersetzt werden.

## Warum dieses Paket existiert

Wir möchten nicht, dass OpenAPI-Dokumentation zu einer zweiten manuellen Implementierung der API wird. Routes, Symfony attributes, DTOs und View objects beschreiben bereits den größten Teil der API. Das Paket nutzt diese Quellen und lässt manuellen OpenAPI-Code für Ausnahmefälle.
