<?php

declare(strict_types=1);

namespace Sunrise\Symfony\OpenApi\OperationEnricher;

use ReflectionAttribute;
use ReflectionClass;
use ReflectionMethod;
use Sunrise\Http\Router\OpenApi\OpenApiOperationEnricherInterface;
use Sunrise\Http\Router\OpenApi\Type;
use Sunrise\Http\Router\RouteInterface;
use Symfony\Component\HttpKernel\Attribute\MapUploadedFile;

/**
 * @since 1.0.0
 */
final class MapUploadedFileOperationEnricher implements OpenApiOperationEnricherInterface
{
    /**
     * @inheritDoc
     */
    public function enrichOperation(
        RouteInterface $route,
        ReflectionClass|ReflectionMethod $requestHandler,
        array &$operation,
    ): void {
        if (! $requestHandler instanceof ReflectionMethod) {
            return;
        }

        foreach ($requestHandler->getParameters() as $requestHandlerParameter) {
            /** @var list<ReflectionAttribute<MapUploadedFile>> $annotations */
            $annotations = $requestHandlerParameter->getAttributes(MapUploadedFile::class);
            if ($annotations === []) {
                continue;
            }

            $mapUploadedFile = $annotations[0]->newInstance();

            $fileName = $mapUploadedFile->name ?? $requestHandlerParameter->name;

            $fileSchema = [
                'type' => Type::OAS_TYPE_NAME_STRING,
                'format' => 'binary',
            ];

            if ($requestHandlerParameter->isVariadic()) {
                $fileSchema = [
                    'type' => Type::OAS_TYPE_NAME_ARRAY,
                    'items' => $fileSchema,
                ];
            }

            $operation['requestBody']['content']['multipart/form-data']['schema']['type'] = Type::OAS_TYPE_NAME_OBJECT;
            // phpcs:ignore Generic.Files.LineLength
            $operation['requestBody']['content']['multipart/form-data']['schema']['properties'][$fileName] = $fileSchema;

            if (!$requestHandlerParameter->isDefaultValueAvailable() && !$requestHandlerParameter->allowsNull()) {
                $operation['requestBody']['content']['multipart/form-data']['schema']['required'][] = $fileName;
                $operation['requestBody']['required'] = true;
            }
        }
    }

    public function getWeight(): int
    {
        return 10;
    }
}
