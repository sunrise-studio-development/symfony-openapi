<?php

declare(strict_types=1);

namespace Sunrise\Symfony\OpenApi\Tests\Fixture;

use DateTimeImmutable;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapDateTime;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\HttpKernel\Attribute\MapQueryString;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\HttpKernel\Attribute\MapUploadedFile;
use Symfony\Component\HttpKernel\Attribute\Serialize;

final class ControllerFixture
{
    public function __invoke(): void
    {
    }

    public function queryParameter(
        #[MapQueryParameter(name: 'foo')]
        int $bar,
        #[MapQueryParameter]
        ?string $baz = null,
        #[MapQueryParameter]
        string ...$qux,
    ): void {
    }

    public function queryParameterWithUnmappedParameter(
        int $foo,
        #[MapQueryParameter]
        string $bar,
    ): void {
    }

    public function queryString(
        #[MapQueryString]
        DtoFixture $query,
        #[MapQueryString(key: 'filter')]
        DtoFixture $filter,
    ): void {
    }

    public function queryStringWithUnmappedParameter(
        string $foo,
        #[MapQueryString]
        DtoFixture $bar,
    ): void {
    }

    public function requestPayload(
        #[MapRequestPayload(acceptFormat: 'json')]
        DtoFixture $payload,
    ): void {
    }

    public function requestPayloadWithDefaultFormat(
        #[MapRequestPayload]
        DtoFixture $payload,
    ): void {
    }

    public function requestPayloadWithUnmappedParameter(
        string $foo,
        #[MapRequestPayload]
        DtoFixture $bar,
    ): void {
    }

    /**
     * @param array<array-key, DtoFixture> $payload
     */
    public function requestPayloadList(
        #[MapRequestPayload(acceptFormat: ['json'], type: DtoFixture::class)]
        array $payload,
    ): void {
    }

    public function uploadedFile(
        #[MapUploadedFile(name: 'avatar')]
        UploadedFile $file,
        #[MapUploadedFile]
        UploadedFile ...$attachments,
    ): void {
    }

    public function uploadedFileWithUnmappedParameter(
        string $foo,
        #[MapUploadedFile]
        UploadedFile $bar,
    ): void {
    }

    public function pathVariable(int $petId): void
    {
    }

    public function enumPathVariable(StringEnumFixture $status): void
    {
    }

    public function entityPathVariable(EntityFixture $pet): void
    {
    }

    public function timestamp(
        #[MapDateTime(format: 'Y-m-d')]
        DateTimeImmutable $createdAt,
    ): void {
    }

    public function disabledTimestamp(
        #[MapDateTime(format: 'Y-m-d', disabled: true)]
        DateTimeImmutable $createdAt,
    ): void {
    }

    public function defaultTimestamp(DateTimeImmutable $createdAt): void
    {
    }

    #[Serialize(code: 201)]
    public function serializableResponse(): DtoFixture
    {
        return new DtoFixture();
    }

    public function symfonyResponse(): JsonResponse
    {
        return new JsonResponse();
    }

    public function emptyResponse(): void
    {
    }
}
