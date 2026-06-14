<?php

declare(strict_types=1);

namespace Sunrise\Symfony\OpenApi;

use InvalidArgumentException;
use Override;
use ReflectionClass;
use ReflectionException;
use ReflectionMethod;
use Sunrise\Http\Router\ReferenceResolver;
use Sunrise\Http\Router\RequestHandlerReflectorInterface;

final class RequestHandlerReflector implements RequestHandlerReflectorInterface
{
    /**
     * @inheritDoc
     *
     * @link https://symfony.com/doc/current/controller/service.html
     */
    #[Override]
    public function reflectRequestHandler(mixed $reference): ReflectionClass|ReflectionMethod
    {
        if (\is_string($reference)) {
            $method = \str_contains($reference, '::') ? $reference : $reference . '::__invoke';

            try {
                // @phpstan-ignore function.alreadyNarrowedType
                if (\method_exists(ReflectionMethod::class, 'createFromMethodName')) {
                    /** @var ReflectionMethod */
                    return ReflectionMethod::createFromMethodName($method);
                }

                return new ReflectionMethod($method);
            } catch (ReflectionException) {
            }
        }

        throw new InvalidArgumentException(\sprintf(
            'The request handler reference "%s" could not be reflected.',
            ReferenceResolver::stringifyReference($reference),
        ));
    }
}
