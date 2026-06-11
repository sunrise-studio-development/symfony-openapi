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
        return $this->route->hasDefault($name) ? $this->route->getDefault($name) : $default;
    }

    /**
     * @inheritDoc
     */
    public function withAddedAttributes(array $attributes): static
    {
        return new self($this->name, (clone $this->route)->addDefaults($attributes));
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
        /** @var array<array-key, string>|string|null $tags */
        $tags = $this->route->getOption('tags');

        return (array) $tags;
    }

    public function getSummary(): string
    {
        /** @var string|null $summary */
        $summary = $this->route->getOption('summary');

        return (string) $summary;
    }

    public function getDescription(): string
    {
        /** @var string|null $description */
        $description = $this->route->getOption('description');

        return (string) $description;
    }

    public function isDeprecated(): bool
    {
        foreach (['deprecated', 'is_deprecated', 'isDeprecated'] as $option) {
            if ($this->route->hasOption($option)) {
                return (bool) $this->route->getOption($option);
            }
        }

        return false;
    }

    public function isApiRoute(): bool
    {
        foreach (['api', 'is_api', 'isApi'] as $option) {
            if ($this->route->hasOption($option)) {
                return (bool) $this->route->getOption($option);
            }
        }

        return \str_starts_with($this->route->getPath(), '/api/');
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
