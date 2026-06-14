<?php

declare(strict_types=1);

namespace Sunrise\Symfony\OpenApi\Tests;

use PHPUnit\Framework\TestCase;
use Sunrise\Symfony\OpenApi\ResponseMetadataResolver;
use Symfony\Component\Routing\Route;

final class ResponseMetadataResolverTest extends TestCase
{
    public function testResolveResponseFormat(): void
    {
        $route = $this->createMock(Route::class);
        $route
            ->expects($this->exactly(2))
            ->method('getOption')
            ->willReturnMap([
                ['response_code', 201],
                ['response_format', 'json'],
            ]);

        $metadata = (new ResponseMetadataResolver())->resolveResponseMetadata(
            $route,
            new \ReflectionMethod(self::class, __FUNCTION__),
        );

        self::assertSame(201, $metadata->code);
        self::assertSame(['json'], $metadata->formats);
    }

    public function testResolveResponseFormats(): void
    {
        $route = $this->createMock(Route::class);
        $route
            ->expects($this->exactly(3))
            ->method('getOption')
            ->willReturnMap([
                ['response_code', null],
                ['response_format', null],
                ['response_formats', ['json', 'xml']],
            ]);

        $metadata = (new ResponseMetadataResolver())->resolveResponseMetadata(
            $route,
            new \ReflectionMethod(self::class, __FUNCTION__),
        );

        self::assertNull($metadata->code);
        self::assertSame(['json', 'xml'], $metadata->formats);
    }
}
