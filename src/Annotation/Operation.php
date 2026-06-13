<?php

declare(strict_types=1);

namespace Sunrise\Symfony\OpenApi\Annotation;

use Attribute;
use Override;
use Sunrise\Http\Router\OpenApi\Annotation\OperationInterface;

#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
final readonly class Operation implements OperationInterface
{
    public function __construct(
        /** @var array<array-key, mixed> */
        private array $operation,
    ) {
    }

    /**
     * @inheritDoc
     */
    #[Override]
    public function getOperation(): array
    {
        return $this->operation;
    }
}
