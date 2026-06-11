<?php

declare(strict_types=1);

namespace Sunrise\Symfony\OpenApi;

use InvalidArgumentException;
use ReflectionClass;
use ReflectionException;
use ReflectionMethod;
use Sunrise\Http\Router\ReferenceResolver;
use Sunrise\Http\Router\RequestHandlerReflectorInterface;

/**
 * @since 1.0.0
 */
final readonly class RequestHandlerReflector implements RequestHandlerReflectorInterface
{
    /**
     * @inheritDoc
     *
     * @link https://symfony.com/doc/current/controller/service.html
     */
    public function reflectRequestHandler(mixed $reference): ReflectionClass|ReflectionMethod
    {
        if (\is_string($reference)) {
            try {
                $method = \str_contains($reference, '::')
                    ? $reference
                    : $reference . '::__invoke';

                /** @psalm-var ReflectionMethod */
                return \method_exists(ReflectionMethod::class, 'createFromMethodName')
                    ? ReflectionMethod::createFromMethodName($method)
                    : new ReflectionMethod($method);
            } catch (ReflectionException) {
            }
        }

        throw new InvalidArgumentException(\sprintf(
            'The request handler reference "%s" could not be reflected.',
            ReferenceResolver::stringifyReference($reference),
        ));
    }
}
