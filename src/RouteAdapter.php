<?php

declare(strict_types=1);

namespace Sunrise\Symfony\OpenApi;

use Override;
use Sunrise\Http\Router\RouteInterface;
use Symfony\Component\Routing\Route;

final readonly class RouteAdapter implements RouteInterface, SymfonyRouteAwareInterface
{
    public function __construct(
        private string $name,
        private Route $route,
        private RouteMetadata $metadata,
    ) {
    }

    #[Override]
    public function getName(): string
    {
        return $this->name;
    }

    #[Override]
    public function getPath(): string
    {
        return $this->route->getPath();
    }

    #[Override]
    public function getRequestHandler(): mixed
    {
        return $this->route->getDefault('_controller');
    }

    /**
     * @inheritDoc
     */
    #[Override]
    public function getPatterns(): array
    {
        /** @var array<string, string> */
        return $this->route->getRequirements();
    }

    /**
     * @inheritDoc
     */
    #[Override]
    public function getMethods(): array
    {
        return $this->route->getMethods();
    }

    /**
     * @inheritDoc
     */
    #[Override]
    public function getAttributes(): array
    {
        /** @var array<string, mixed> */
        return $this->route->getDefaults();
    }

    #[Override]
    public function hasAttribute(string $name): bool
    {
        return $this->route->hasDefault($name);
    }

    #[Override]
    public function getAttribute(string $name, mixed $default = null): mixed
    {
        return $this->route->hasDefault($name) ? $this->route->getDefault($name) : $default;
    }

    /**
     * @inheritDoc
     */
    #[Override]
    public function withAddedAttributes(array $attributes): static
    {
        return new self($this->name, (clone $this->route)->addDefaults($attributes), $this->metadata);
    }

    /**
     * @inheritDoc
     */
    #[Override]
    public function getMiddlewares(): array
    {
        return [];
    }

    /**
     * @inheritDoc
     */
    #[Override]
    public function getConsumedMediaTypes(): array
    {
        return [];
    }

    /**
     * @inheritDoc
     */
    #[Override]
    public function getProducedMediaTypes(): array
    {
        return [];
    }

    /**
     * @inheritDoc
     */
    #[Override]
    public function getTags(): array
    {
        return $this->metadata->tags;
    }

    #[Override]
    public function getSummary(): string
    {
        return $this->metadata->summary;
    }

    #[Override]
    public function getDescription(): string
    {
        return $this->metadata->description;
    }

    #[Override]
    public function isDeprecated(): bool
    {
        return $this->metadata->isDeprecated;
    }

    #[Override]
    public function isApiRoute(): bool
    {
        return $this->metadata->isApi;
    }

    #[Override]
    public function getPattern(): ?string
    {
        return null;
    }

    #[Override]
    public function getSymfonyRoute(): Route
    {
        return $this->route;
    }
}
