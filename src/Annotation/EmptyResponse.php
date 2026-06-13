<?php

declare(strict_types=1);

namespace Sunrise\Symfony\ApiFoundation\Annotation;

use Attribute;
use Sunrise\Http\Router\OpenApi\Annotation\OperationInterface;

#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
final readonly class EmptyResponse implements OperationInterface
{
    public function __construct(
        private int $code = 204,
    ) {
    }

    /**
     * @inheritDoc
     */
    public function getOperation(): array
    {
        return [
            'responses' => [
                $this->code => [
                    'description' => 'Empty Response',
                ],
            ],
        ];
    }
}
