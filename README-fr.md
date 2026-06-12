# OpenAPI Generator for Symfony Routing

![PHP](https://img.shields.io/packagist/dependency-v/sunrise-studio/symfony-openapi/php?style=social&logo=php&label=PHP)
![Coverage](https://img.shields.io/scrutinizer/coverage/g/sunrise-studio-development/symfony-openapi?style=social)
![Code quality](https://img.shields.io/scrutinizer/quality/g/sunrise-studio-development/symfony-openapi?style=social)

Langues: [English](README.md) | [Русский](README-ru.md) | [Українська](README-uk.md) | [Français](README-fr.md) | [Deutsch](README-de.md)

`sunrise-studio/symfony-openapi` génère un document OpenAPI à partir des routes Symfony, des signatures de contrôleurs, des attributs Symfony HTTP Kernel et des classes PHP DTO/View.

Notre objectif est simple: les développeurs ne devraient pas écrire de grands blocs d'attributs OpenAPI pour les endpoints API courants. Nous pensons que la documentation doit suivre le code qui décrit déjà l'API: routes, DTO d'entrée, objets de query, fichiers uploadés, variables de chemin et objets de vue en sortie. Les fragments OpenAPI manuels doivent rester réservés aux cas exceptionnels.

Le paquet utilise les mécanismes OpenAPI de [Sunrise HTTP Router](https://github.com/sunrise-php/http-router), mais l'API destinée à Symfony se trouve dans le namespace `Sunrise\Symfony\OpenApi`.

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

Importez les routes du paquet:

```yaml
# config/routes.yaml
openapi:
  resource: '@OpenApiBundle/config/routes.php'
```

Cela importe deux routes:

| Route | Controller | Rôle |
| --- | --- | --- |
| `GET /openapi` | `OpenApiController` | Sert le document OpenAPI JSON généré. |
| `GET /swagger.html` | `SwaggerController` | Sert Swagger UI configuré pour lire `/openapi`. |

Ces deux routes sont enregistrées avec `api: false`, elles ne sont donc pas incluses dans la documentation API générée.

Si vous ne voulez qu'une seule route, importez directement le fichier concerné:

```yaml
openapi_document:
  resource: '@OpenApiBundle/config/routes/openapi.php'

swagger_ui:
  resource: '@OpenApiBundle/config/routes/swagger.php'
```

Références Symfony:

- [Routing](https://symfony.com/doc/current/routing.html)
- [Bundles](https://symfony.com/doc/current/bundles.html)

## Configuration De Base

Une configuration d'application typique ressemble à ceci:

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
| `openapi.initial_document` | OpenAPI version + `API` title | Document de base fusionné avec les paths et schemas générés. |
| `openapi.initial_operation` | `responses: []` | Operation de base utilisée pour chaque route. |
| `openapi.document_filename` | `%kernel.project_dir%/var/openapi.json` | Emplacement où la commande écrit le document généré. |
| `openapi.default_timestamp_format` | `OpenApiConfiguration::DEFAULT_TIMESTAMP_FORMAT` | Format PHP `date()` utilisé pour générer les valeurs OpenAPI `example` des schémas date/heure. |
| `openapi.default_empty_response_status` | `204` | Status par défaut pour les méthodes de contrôleur `void`. |
| `openapi.default_response_status` | `200` | Status par défaut pour les objets de retour sérialisés. |
| `openapi.default_response_formats` | `['json']` | Formats Symfony par défaut pour les objets de retour sérialisés. |

`SwaggerConfiguration` peut aussi être remplacé ou configuré comme service si vous avez besoin d'assets Swagger UI personnalisés, de variables de template ou d'une URL OpenAPI différente.

## Générer Le Document

Exécutez:

```bash
php bin/console openapi:build-document
```

La commande lit la collection de routes Symfony, résout les metadata de routes, garde uniquement les routes API, les adapte au document builder OpenAPI de Sunrise et enregistre le document dans `openapi.document_filename`.

Ensuite:

- `/openapi` retourne le document JSON généré.
- `/swagger.html` ouvre Swagger UI.

## Route Options

Les route options sont le moyen par défaut de décrire les metadata au niveau de la route:

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

Options supportées:

| Option | Type | Rôle |
| --- | --- | --- |
| `tags` | `string|string[]` | Tags OpenAPI de l'operation. |
| `summary` | `string` | Summary OpenAPI de l'operation. |
| `description` | `string` | Description OpenAPI de l'operation. |
| `deprecated`, `is_deprecated`, `isDeprecated` | `bool` | Marque une operation comme deprecated. |
| `api`, `is_api`, `isApi` | `bool` | Inclut ou exclut la route du document généré. |
| `response_status` | `int` | Remplace le status de réponse généré. |
| `response_formats` | `string|string[]` | Formats de réponse Symfony, par exemple `json` ou `xml`. |

Si aucune option API n'est définie, les routes dont le path commence par `/api/` sont traitées comme des routes API.

Si vous ne voulez pas stocker les tags, summaries, descriptions ou la règle de filtrage API dans les route options, remplacez `RouteMetadataResolverInterface` par votre propre service.

## Attributs Symfony

Le paquet prend en charge les attributs de controller value resolver Symfony. Voir la [documentation Symfony](https://symfony.com/doc/current/controller/value_resolver.html).

### Request Body

`#[MapRequestPayload]` crée un `requestBody` OpenAPI.

```php
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;

public function create(#[MapRequestPayload(acceptFormat: 'json')] CreatePetRequest $request): PetView
{
    // ...
}
```

Comportement:

- Le type PHP du paramètre devient le request schema.
- `acceptFormat` est converti via le format de requête Symfony en media type, par exemple `json` en `application/json`.
- Si le paramètre est required en PHP, le request body OpenAPI est marqué required.
- Pour les array payloads, `MapRequestPayload(type: SomeDto::class)` décrit le type des items.

### Query Object

`#[MapQueryString]` décrit un query object.

```php
use Symfony\Component\HttpKernel\Attribute\MapQueryString;

public function list(#[MapQueryString] PetSearchQuery $query): JsonResponse
{
    // ...
}
```

Si `key` vaut `null`, l'objet décrit tout le query string avec `style: form`. Si `key` est défini, le paramètre utilise `style: deepObject`.

### Query Parameter

`#[MapQueryParameter]` décrit des query parameters scalaires, enum, date, UID ou array.

```php
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;

public function find(
    #[MapQueryParameter] PetStatus $status,
    #[MapQueryParameter] string ...$tags,
): JsonResponse {
    // ...
}
```

Les paramètres variadiques sont décrits comme des arrays et ne sont pas marqués required.

### Uploaded Files

`#[MapUploadedFile]` ajoute un request body `multipart/form-data` avec des champs binary.

```php
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpKernel\Attribute\MapUploadedFile;

public function upload(#[MapUploadedFile(name: 'photo')] UploadedFile $file): JsonResponse
{
    // ...
}
```

Les uploaded files variadiques sont décrits comme un array de binary strings et ne sont pas marqués required.

### Path Variables

Les path variables Symfony sont lues depuis les routes compilées. Les requirements sont convertis en patterns de schema OpenAPI.

```php
#[Route('/api/pets/{petId}', requirements: ['petId' => '\d+'])]
public function show(int $petId): PetView
{
    // ...
}
```

Types de paramètres reflétés supportés pour les path variables:

- `bool`
- `int`
- `float`
- `string`
- `BackedEnum`
- `DateTimeInterface`
- `Symfony\Component\Uid\AbstractUid`

Les aliases de mapping de route Symfony sont supportés pour les mappings simples comme `['id' => 'petId']`. Les mappings de style entity comme `{id:pet.id}` ne sont volontairement pas décrits comme des schemas d'objet; la path variable publique reste documentée comme string sauf si un paramètre scalaire supporté est trouvé.

### Date Et Time

`#[MapDateTime(format: ...)]` change l'exemple date/time généré pour les paramètres de contrôleur.

```php
use Symfony\Component\HttpKernel\Attribute\MapDateTime;

public function history(#[MapDateTime(format: 'Y-m-d')] DateTimeImmutable $date): JsonResponse
{
    // ...
}
```

## Génération Des Responses

Le comportement par défaut des responses est volontairement petit et prévisible:

| Controller return type | Response générée |
| --- | --- |
| `void` | Réponse vide, status par défaut `204`. |
| Sous-classe Symfony `Response` | Aucun response content automatique. Utilisez `#[Operation]` pour les cas manuels. |
| Tout autre named return type | Serialized response body, status par défaut `200`, format par défaut `json`. |

Exemple:

```php
#[Route('/api/pets/{id}', options: ['response_status' => 200])]
public function show(int $id): PetView
{
    // ...
}
```

Si une route retourne un view object custom, le return type est résolu par le système de PHP type schema resolvers et utilisé comme response schema.

Si votre projet enveloppe les responses, par exemple `{data: ..., meta: ...}`, remplacez `ResponseMetadataResolverInterface` ou les response operation enrichers par vos propres services.

## Fragments OpenAPI Manuels

La plupart des endpoints ne devraient pas avoir besoin de fragments OpenAPI manuels. Pour les cas exceptionnels, utilisez `#[Operation]`:

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

Le fragment est fusionné dans l'operation générée par le `OpenApiDocumentManager` de Sunrise.

## Annotations OpenAPI Symfony

Le paquet fournit des annotations orientées Symfony afin que le code applicatif n'ait pas besoin d'importer des namespaces router ou hydrator pour les tâches courantes de schema OpenAPI:

| Annotation | Target | Rôle |
| --- | --- | --- |
| `#[Operation]` | class, method | Ajoute un fragment OpenAPI operation manuel. |
| `#[ItemType]` | property, parameter | Décrit le type des items d'un array. |
| `#[SchemaName]` | class | Remplace le nom du component schema. |
| `#[PropertyName]` | property | Remplace le nom de propriété OpenAPI. |
| `#[IgnoreProperty]` | property | Exclut une propriété du schema d'objet. |
| `#[TimestampFormat]` | property | Remplace le format d'exemple date/time. |

## PHP Type Schema Resolution

Le bundle enregistre explicitement les schema resolvers de Sunrise et remplace le timestamp resolver par une version aware Symfony.

Resolvers actifs:

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

Si votre projet a un type custom qui nécessite un schema custom, implémentez `Sunrise\Http\Router\OpenApi\OpenApiPhpTypeSchemaResolverInterface` et enregistrez votre resolver dans le service `OpenApiPhpTypeSchemaResolverManagerInterface`.

## Object Schema Resolver

`Sunrise\Http\Router\OpenApi\PhpTypeSchemaResolver\ObjectPhpTypeSchemaResolver` est le resolver principal pour les DTOs et view objects.

Il lit les classes PHP directement:

- les classes instantiable non-internal sont supportées;
- les propriétés public/private/protected sont reflétées;
- les types de propriétés deviennent des schemas de propriétés OpenAPI;
- les propriétés sans valeur par défaut sont marquées required;
- les valeurs par défaut sont émises quand elles sont scalar ou backed enum;
- les defaults des propriétés promoted par constructeur sont supportés;
- `#[SchemaName]` change le nom du component schema;
- `#[PropertyName]` change le nom de propriété;
- `#[IgnoreProperty]` exclut une propriété;
- `#[ItemType]` décrit les propriétés array;
- `#[TimestampFormat]` change les exemples date/time.

Ce resolver n'utilise pas les metadata Symfony Serializer. Il ne lit pas les serializer groups, getters, setters, `SerializedName`, name converters ou règles de conversion camelCase/snake_case.

Nous recommandons des DTO et View classes explicites avec des propriétés typées. Si vous avez besoin d'une forme externe différente, créez un nouveau View object et mappez votre objet de domaine vers celui-ci. Cela garde la recherche, le refactoring et la génération de schema simples.

Si votre équipe a besoin d'un support Symfony Serializer first-class, ouvrez une issue. Nous envisagerons de l'ajouter sous forme de resolver optionnel ou de strategy layer.

Référence Symfony Serializer: [Serializer](https://symfony.com/doc/current/serializer.html).

## Points D'extension

Le paquet est volontairement construit à partir de petits services:

| Service/interface | Rôle |
| --- | --- |
| `RouteMetadataResolverInterface` | Contrôle tags, summary, description, deprecation et API filtering. |
| `ResponseMetadataResolverInterface` | Contrôle response status et response formats. |
| `OpenApiOperationEnricherInterface` | Ajoute request parameters, request bodies, responses ou custom operation data. |
| `OpenApiPhpTypeSchemaResolverInterface` | Convertit les types PHP en schemas OpenAPI. |
| `OpenApiPathBuilderInterface` | Convertit les paths de routes Symfony en paths OpenAPI. |

Vous pouvez remplacer ces services dans votre container Symfony lorsque la politique du projet diffère des defaults.

## Pourquoi Ce Paquet Existe

Nous avons vu beaucoup d'APIs documentées par de longs blocs d'attributs OpenAPI manuels. Cela fonctionne, mais la documentation devient souvent une deuxième implémentation de la même API.

Nous voulons que le chemin normal soit différent:

- les routes décrivent les paths et HTTP methods;
- les attributs Symfony décrivent le request mapping;
- les DTOs décrivent les input payloads;
- les view objects décrivent les output payloads;
- les route options décrivent les human operation metadata;
- le code spécifique OpenAPI est utilisé seulement lorsque le modèle automatique ne suffit pas.

Plus la documentation est proche du vrai code applicatif, plus il est difficile pour les deux de diverger.
