<?php

declare(strict_types=1);

namespace Sunrise\Symfony\OpenApi\Tests\Annotation;

use PHPUnit\Framework\TestCase;
use Sunrise\Symfony\OpenApi\Annotation\EmptyResponse;
use Sunrise\Symfony\OpenApi\Annotation\ItemType;
use Sunrise\Symfony\OpenApi\Annotation\Operation;
use Sunrise\Symfony\OpenApi\Annotation\PropertyName;
use Sunrise\Symfony\OpenApi\Annotation\SchemaName;
use Sunrise\Symfony\OpenApi\Annotation\TimestampFormat;

final class AnnotationTest extends TestCase
{
    public function testEmptyResponse(): void
    {
        self::assertSame([
            'responses' => [
                204 => [
                    'description' => 'The operation was successful.',
                ],
            ],
        ], new EmptyResponse()->getOperation());

        self::assertSame([
            'responses' => [
                202 => [
                    'description' => 'Accepted.',
                ],
            ],
        ], new EmptyResponse(202, 'Accepted.')->getOperation());
    }

    public function testItemType(): void
    {
        $itemType = new ItemType('Foo', allowsNull: true, limit: 10);

        self::assertSame('Foo', $itemType->name);
        self::assertTrue($itemType->allowsNull);
        self::assertSame(10, $itemType->limit);
    }

    public function testOperation(): void
    {
        $operation = [
            'summary' => 'Summary',
        ];

        self::assertSame($operation, new Operation($operation)->getOperation());
    }

    public function testPropertyName(): void
    {
        self::assertSame('foo', new PropertyName('foo')->getPropertyName());
    }

    public function testSchemaName(): void
    {
        self::assertSame('Foo', new SchemaName('Foo')->getSchemaName());
    }

    public function testTimestampFormat(): void
    {
        self::assertSame('Y-m-d', new TimestampFormat('Y-m-d')->getTimestampFormat());
    }
}
