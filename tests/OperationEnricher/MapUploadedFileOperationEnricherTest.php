<?php

declare(strict_types=1);

namespace Sunrise\Symfony\OpenApi\Tests\OperationEnricher;

use PHPUnit\Framework\TestCase;
use Sunrise\Symfony\OpenApi\OperationEnricher\MapUploadedFileOperationEnricher;
use Sunrise\Symfony\OpenApi\Tests\TestKit;

final class MapUploadedFileOperationEnricherTest extends TestCase
{
    use TestKit;

    public function testEnrichOperation(): void
    {
        $operation = [];

        (new MapUploadedFileOperationEnricher())->enrichOperation(
            $this->createRoute(),
            self::createControllerReflection('uploadedFile'),
            $operation,
        );

        self::assertSame([
            'content' => [
                'multipart/form-data' => [
                    'schema' => [
                        'type' => 'object',
                        'properties' => [
                            'avatar' => [
                                'type' => 'string',
                                'format' => 'binary',
                            ],
                            'attachments' => [
                                'type' => 'array',
                                'items' => [
                                    'type' => 'string',
                                    'format' => 'binary',
                                ],
                            ],
                        ],
                        'required' => ['avatar'],
                    ],
                ],
            ],
            'required' => true,
        ], $operation['requestBody']);
    }
}
