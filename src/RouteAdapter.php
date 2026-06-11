<?php

declare(strict_types=1);

namespace Sunrise\Symfony\OpenApi;

use Sunrise\Http\Router\RouteInterface;
use Symfony\Component\Routing\Route;

/**
 * @since 1.0.0
 */
final readonly class RouteAdapter implements RouteInterface, SymfonyRouteAwareInterface
{
    public function __construct(
        private string $name,
        private Route $route,
        private RouteMetadata $metadata,
    ) {
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getPath(): string
    {
        return $this->route->getPath();
    }

    public function getRequestHandler(): mixed
    {
        return $this->route->getDefault('_controller');
    }

    /**
     * @inheritDoc
     */
    public function getPatterns(): array
    {
        /** @var array<string, string> */
        return $this->route->getRequirements();
    }

    /**
     * @inheritDoc
     */
    public function getMethods(): array
    {
        return $this->route->getMethods();
    }

    /**
     * @inheritDoc
     */
    public function getAttributes(): array
    {
        /** @var array<string, mixed> */
        return $this->route->getDefaults();
    }

    public function hasAttribute(string $name): bool
    {
        return $this->route->hasDefault($name);
    }

    public function getAttribute(string $name, mixed $default = null): mixed
    {
        return $this->route->hasDefault($name)
            ? $this->route->getDefault($name)
            : $default;
    }

    /**
     * @inheritDoc
     */
    public function withAddedAttributes(array $attributes): static
    {
        $route = (clone $this->route)->addDefaults($attributes);

        return new self($this->name, $route, $this->metadata);
    }

    /**
     * @inheritDoc
     */
    public function getMiddlewares(): array
    {
        return [];
    }

    /**
     * @inheritDoc
     */
    public function getConsumedMediaTypes(): array
    {
        return [];
    }

    /**
     * @inheritDoc
     */
    public function getProducedMediaTypes(): array
    {
        return [];
    }

    /**
     * @inheritDoc
     */
    public function getTags(): array
    {
        return $this->metadata->tags;
    }

    public function getSummary(): string
    {
        return $this->metadata->summary;
    }

    public function getDescription(): string
    {
        return $this->metadata->description;
    }

    public function isDeprecated(): bool
    {
        return $this->metadata->isDeprecated;
    }

    public function isApiRoute(): bool
    {
        return $this->metadata->isApi;
    }

    public function getPattern(): ?string
    {
        return null;
    }

    public function getSymfonyRoute(): Route
    {
        return $this->route;
    }
}
