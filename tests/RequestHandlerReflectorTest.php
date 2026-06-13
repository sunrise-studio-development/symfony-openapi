<?php

declare(strict_types=1);

namespace Sunrise\Symfony\OpenApi\Tests;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;
use Sunrise\Symfony\OpenApi\RequestHandlerReflector;
use Sunrise\Symfony\OpenApi\Tests\Fixture\ControllerFixture;

final class RequestHandlerReflectorTest extends TestCase
{
    public function testInvokableController(): void
    {
        $reflection = new RequestHandlerReflector()->reflectRequestHandler(ControllerFixture::class);

        self::assertInstanceOf(ReflectionMethod::class, $reflection);
        self::assertSame('__invoke', $reflection->getName());
    }

    public function testControllerMethod(): void
    {
        $reflection = new RequestHandlerReflector()->reflectRequestHandler(
            ControllerFixture::class . '::symfonyResponse',
        );

        self::assertInstanceOf(ReflectionMethod::class, $reflection);
        self::assertSame('symfonyResponse', $reflection->getName());
    }

    public function testUnknownReference(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The request handler reference "unknown" could not be reflected.');

        new RequestHandlerReflector()->reflectRequestHandler('unknown');
    }

    public function testInvalidReference(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The request handler reference "array" could not be reflected.');

        new RequestHandlerReflector()->reflectRequestHandler([]);
    }
}
