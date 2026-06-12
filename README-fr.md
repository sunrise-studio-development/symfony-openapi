# OpenAPI Generator for Symfony Routing

![PHP](https://img.shields.io/packagist/dependency-v/sunrise-studio/symfony-openapi/php?style=social&logo=php&label=PHP)
![Coverage](https://img.shields.io/scrutinizer/coverage/g/sunrise-studio-development/symfony-openapi?style=social)
![Code quality](https://img.shields.io/scrutinizer/quality/g/sunrise-studio-development/symfony-openapi?style=social)

Langues: [English](README.md) | [Русский](README-ru.md) | [Українська](README-uk.md) | [Français](README-fr.md) | [Deutsch](README-de.md)

`sunrise-studio/symfony-openapi` génère un document OpenAPI à partir des routes Symfony, des signatures de contrôleurs, des attributs Symfony HTTP Kernel et des classes PHP DTO/View.

Notre objectif est simple: le développeur ne devrait pas écrire de grands blocs d'attributs OpenAPI pour les endpoints courants. La documentation doit suivre le code qui décrit déjà l'API: routes, DTO d'entrée, objets de requête, fichiers uploadés, variables de chemin et objets de vue en sortie.

Le paquet utilise le coeur OpenAPI de [Sunrise HTTP Router](https://github.com/sunrise-php/http-router), mais l'API Symfony est dans `Sunrise\Symfony\OpenApi`.

## Installation

```bash
composer require sunrise-studio/symfony-openapi
```

Enregistrez le bundle:

```php
// config/bundles.php
return [
    Symfony\Bundle\FrameworkBundle\FrameworkBundle::class => ['all' => true],
    Sunrise\Symfony\OpenApi\OpenApiBundle::class => ['all' => true],
];
```

Importez les routes:

```yaml
# config/routes.yaml
openapi:
  resource: '@OpenApiBundle/config/routes.php'
```

Routes fournies:

| Route | Controller | Rôle |
| --- | --- | --- |
| `GET /openapi` | `OpenApiController` | Sert le document OpenAPI JSON. |
| `GET /swagger.html` | `SwaggerController` | Sert Swagger UI configuré pour `/openapi`. |

Ces routes ont `api: false`, elles ne sont donc pas incluses dans la documentation générée.

Vous pouvez importer une seule route:

```yaml
openapi_document:
  resource: '@OpenApiBundle/config/routes/openapi.php'

swagger_ui:
  resource: '@OpenApiBundle/config/routes/swagger.php'
```

Documentation Symfony: [Routing](https://symfony.com/doc/current/routing.html), [Bundles](https://symfony.com/doc/current/bundles.html).

## Configuration

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

Paramètres utiles:

| Paramètre | Défaut | Rôle |
| --- | --- | --- |
| `openapi.initial_document` | OpenAPI version + `API` title | Document de base. |
| `openapi.initial_operation` | `responses: []` | Operation de base. |
| `openapi.document_filename` | `%kernel.project_dir%/var/openapi.json` | Fichier généré. |
| `openapi.default_timestamp_format` | Sunrise default | Format des exemples de dates. |
| `openapi.default_empty_response_status` | `204` | Status pour `void`. |
| `openapi.default_response_status` | `200` | Status pour les objets sérialisés. |
| `openapi.default_response_formats` | `['json']` | Formats de réponse par défaut. |

`SwaggerConfiguration` peut être remplacé comme service pour changer les assets, les variables de template ou l'URL OpenAPI.

## Commande

```bash
php bin/console openapi:build-document
```

La commande lit `RouterInterface`, filtre les API routes, construit le document et l'enregistre dans `openapi.document_filename`.

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

Options supportées:

- `tags`
- `summary`
- `description`
- `deprecated`, `is_deprecated`, `isDeprecated`
- `api`, `is_api`, `isApi`
- `response_status`
- `response_formats`

Sans option API, un chemin commençant par `/api/` est considéré comme une API route. Pour une autre politique, remplacez `RouteMetadataResolverInterface`.

## Attributs Symfony

Le paquet supporte les attributs de value resolver Symfony: [documentation Symfony](https://symfony.com/doc/current/controller/value_resolver.html).

- `#[MapRequestPayload]` crée un `requestBody`.
- `#[MapQueryString]` décrit un objet query.
- `#[MapQueryParameter]` décrit un paramètre query; les variadiques deviennent des arrays non required.
- `#[MapUploadedFile]` décrit `multipart/form-data`; les variadiques deviennent des arrays de binary strings non required.
- Les path variables viennent des routes Symfony; les requirements deviennent des patterns OpenAPI.
- `#[MapDateTime(format: ...)]` change l'exemple de date.

## Réponses

| Return type | Réponse générée |
| --- | --- |
| `void` | Réponse vide, status `204`. |
| Sous-classe Symfony `Response` | Pas de contenu automatique. Utilisez `#[Operation]`. |
| Autre named return type | Body sérialisé, status `200`, format `json`. |

`response_status` et `response_formats` remplacent les valeurs par défaut. Pour une politique différente, remplacez `ResponseMetadataResolverInterface` ou les response enrichers.

## Annotations

| Annotation | Rôle |
| --- | --- |
| `#[Operation]` | Fragment OpenAPI manuel. |
| `#[ItemType]` | Type des items d'un array. |
| `#[SchemaName]` | Nom du component schema. |
| `#[PropertyName]` | Nom de propriété OpenAPI. |
| `#[IgnoreProperty]` | Exclut une propriété. |
| `#[TimestampFormat]` | Format d'exemple de date. |

## PHP Type Schema Resolution

Resolvers actifs: bool, int, float, string, array, collections ArrayAccess, backed enums, objects, Symfony UID/UUID, timestamp.

Pour vos types, implémentez `Sunrise\Http\Router\OpenApi\OpenApiPhpTypeSchemaResolverInterface` et enregistrez le resolver dans le container.

## Object Schema Resolver

`ObjectPhpTypeSchemaResolver` lit les classes PHP directement: properties, types, valeurs par défaut, required fields, promoted constructor defaults et annotations de schema.

Il n'utilise pas Symfony Serializer metadata. Les groups, getters, setters, `SerializedName`, name converters et conversions camelCase/snake_case ne sont pas lus.

Nous recommandons des DTO/View classes explicites avec des propriétés typées. Si le support Symfony Serializer est nécessaire, ouvrez une issue.

Documentation Symfony Serializer: [Serializer](https://symfony.com/doc/current/serializer.html).

## Extension Points

- `RouteMetadataResolverInterface`
- `ResponseMetadataResolverInterface`
- `OpenApiOperationEnricherInterface`
- `OpenApiPhpTypeSchemaResolverInterface`
- `OpenApiPathBuilderInterface`

Ces services peuvent être remplacés dans le container Symfony.

## Pourquoi ce paquet existe

Nous ne voulons pas que la documentation OpenAPI devienne une deuxième implémentation manuelle de l'API. Les routes, attributs Symfony, DTO et View objects décrivent déjà la majeure partie de l'API. Le paquet utilise ces sources et garde le code OpenAPI manuel pour les cas exceptionnels.
