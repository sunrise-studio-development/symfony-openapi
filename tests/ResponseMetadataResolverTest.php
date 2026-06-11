<?php

declare(strict_types=1);

namespace Sunrise\Symfony\OpenApi\Tests;

use PHPUnit\Framework\TestCase;
use Sunrise\Symfony\OpenApi\ResponseMetadataResolver;
use Symfony\Component\Routing\Route;

final class ResponseMetadataResolverTest extends TestCase
{
    use TestKit;

    public function testResolveResponseMetadata(): void
    {
        $metadata = (new ResponseMetadataResolver())->resolveResponseMetadata(
            new Route('/api/foo', options: [
                'response_status' => 201,
                'response_formats' => ['json'],
            ]),
            self::createControllerReflection('serializableResponse'),
        );

        self::assertSame(201, $metadata->status);
        self::assertSame(['json'], $metadata->formats);
    }
}
