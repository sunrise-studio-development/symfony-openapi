<?php

declare(strict_types=1);

namespace Sunrise\Symfony\OpenApi\Tests\OperationEnricher;

use Override;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use stdClass;
use Sunrise\Symfony\OpenApi\OperationEnricher\MapUploadedFileOperationEnricher;
use Sunrise\Symfony\OpenApi\Tests\TestKit;
use Symfony\Component\HttpKernel\Attribute\MapUploadedFile;

final class MapUploadedFileOperationEnricherTest extends TestCase
{
    use TestKit;

    #[Override]
    protected function setUp(): void
    {
        if (!\class_exists(MapUploadedFile::class)) {
            $this->markTestSkipped('Symfony less than 7.1 does not support.');
        }

        parent::setUp();
    }

    public function testEnrichOperation(): void
    {
        $operation = [];

        (new MapUploadedFileOperationEnricher())->enrichOperation(
            $this->mockRoute(),
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

    public function testNonMethodRequestHandler(): void
    {
        $operation = [];

        (new MapUploadedFileOperationEnricher())->enrichOperation(
            $this->mockRoute(),
            new ReflectionClass(new stdClass()),
            $operation,
        );

        self::assertSame([], $operation);
    }

    public function testUnmappedParameter(): void
    {
        $operation = [];

        (new MapUploadedFileOperationEnricher())->enrichOperation(
            $this->mockRoute(),
            self::createControllerReflection('uploadedFileWithUnmappedParameter'),
            $operation,
        );

        self::assertArrayHasKey(
            'bar',
            $operation['requestBody']['content']['multipart/form-data']['schema']['properties'],
        );
    }

    public function testWeight(): void
    {
        self::assertSame(10, (new MapUploadedFileOperationEnricher())->getWeight());
    }
}
