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
    public function testReflectsInvokableController(): void
    {
        $reflection = (new RequestHandlerReflector())->reflectRequestHandler(ControllerFixture::class);

        self::assertInstanceOf(ReflectionMethod::class, $reflection);
        self::assertSame('__invoke', $reflection->getName());
    }

    public function testReflectsControllerMethod(): void
    {
        $reflection = (new RequestHandlerReflector())->reflectRequestHandler(
            ControllerFixture::class . '::symfonyResponse',
        );

        self::assertInstanceOf(ReflectionMethod::class, $reflection);
        self::assertSame('symfonyResponse', $reflection->getName());
    }

    public function testFailsWhenReferenceIsNotReflectable(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The request handler reference "unknown" could not be reflected.');

        (new RequestHandlerReflector())->reflectRequestHandler('unknown');
    }
}
