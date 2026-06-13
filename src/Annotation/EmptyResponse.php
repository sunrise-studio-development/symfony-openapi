<?php

declare(strict_types=1);

namespace Sunrise\Symfony\OpenApi\Annotation;

use Attribute;
use Override;
use Sunrise\Http\Router\OpenApi\Annotation\OperationInterface;

#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
final readonly class EmptyResponse implements OperationInterface
{
    private const int DEFAULT_CODE = 204;
    private const string DEFAULT_DESCRIPTION = 'The operation was successful.';

    public function __construct(
        private int $code = self::DEFAULT_CODE,
        private string $description = self::DEFAULT_DESCRIPTION,
    ) {
    }

    /**
     * @inheritDoc
     */
    #[Override]
    public function getOperation(): array
    {
        return [
            'responses' => [
                $this->code => [
                    'description' => $this->description,
                ],
            ],
        ];
    }
}
