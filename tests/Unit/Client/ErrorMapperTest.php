<?php

declare(strict_types=1);

namespace KBDataSolutions\Sdk\Tests\Unit\Client;

use KBDataSolutions\Sdk\Client\ErrorMapper;
use KBDataSolutions\Sdk\Exception\ApiException;
use KBDataSolutions\Sdk\Exception\AuthenticationException;
use KBDataSolutions\Sdk\Exception\AuthorizationException;
use KBDataSolutions\Sdk\Exception\ConflictException;
use KBDataSolutions\Sdk\Exception\NotFoundException;
use KBDataSolutions\Sdk\Exception\RateLimitException;
use KBDataSolutions\Sdk\Exception\ValidationException;
use KBDataSolutions\Sdk\Http\TransportResponse;
use PHPUnit\Framework\TestCase;

final class ErrorMapperTest extends TestCase
{
    public function test_maps_401_to_authentication_exception(): void
    {
        $response = new TransportResponse(401, ['X-Request-Id' => 'req-1'], '{"error":{"code":"UNAUTHENTICATED","message":"Unauthenticated."}}');

        $exception = ErrorMapper::mapToException($response);

        self::assertInstanceOf(AuthenticationException::class, $exception);
        self::assertSame('Unauthenticated.', $exception->getMessage());
        self::assertSame('UNAUTHENTICATED', $exception->errorCode());
        self::assertSame('req-1', $exception->requestId());
    }

    public function test_maps_403_to_authorization_exception_carrying_the_sub_case_code(): void
    {
        $response = new TransportResponse(403, [], '{"error":{"code":"SUBSCRIPTION_INACTIVE","message":"Forbidden."}}');

        $exception = ErrorMapper::mapToException($response);

        self::assertInstanceOf(AuthorizationException::class, $exception);
        self::assertSame('SUBSCRIPTION_INACTIVE', $exception->errorCode());
    }

    public function test_maps_404_to_not_found_exception(): void
    {
        $response = new TransportResponse(404, [], '{"error":{"code":"NOT_FOUND","message":"Debtor not found."}}');

        $exception = ErrorMapper::mapToException($response);

        self::assertInstanceOf(NotFoundException::class, $exception);
        self::assertSame('Debtor not found.', $exception->getMessage());
    }

    public function test_maps_409_to_conflict_exception(): void
    {
        $response = new TransportResponse(409, [], '{"error":{"code":"IDEMPOTENCY_KEY_REUSED","message":"Conflict."}}');

        $exception = ErrorMapper::mapToException($response);

        self::assertInstanceOf(ConflictException::class, $exception);
        self::assertSame('IDEMPOTENCY_KEY_REUSED', $exception->errorCode());
    }

    public function test_maps_422_to_validation_exception_with_field_errors(): void
    {
        $body = '{"error":{"code":"VALIDATION_FAILED","message":"Invalid.","details":{"fields":{"type":["The type field is required."]}}}}';
        $response = new TransportResponse(422, [], $body);

        $exception = ErrorMapper::mapToException($response);

        self::assertInstanceOf(ValidationException::class, $exception);
        self::assertSame(['type' => ['The type field is required.']], $exception->fieldErrors());
    }

    public function test_maps_429_to_rate_limit_exception_with_retry_after(): void
    {
        $response = new TransportResponse(429, ['Retry-After' => '30'], '{"error":{"code":"TOO_MANY_REQUESTS","message":"Too many requests."}}');

        $exception = ErrorMapper::mapToException($response);

        self::assertInstanceOf(RateLimitException::class, $exception);
        self::assertSame(30, $exception->retryAfterSeconds());
    }

    public function test_maps_429_without_retry_after_header_to_null(): void
    {
        $response = new TransportResponse(429, [], '{"error":{"code":"TOO_MANY_REQUESTS","message":"Too many requests."}}');

        $exception = ErrorMapper::mapToException($response);

        self::assertInstanceOf(RateLimitException::class, $exception);
        self::assertNull($exception->retryAfterSeconds());
    }

    public function test_maps_any_other_status_to_api_exception(): void
    {
        $response = new TransportResponse(500, [], '{"error":{"code":"INTERNAL_ERROR","message":"Something broke."}}');

        $exception = ErrorMapper::mapToException($response);

        self::assertInstanceOf(ApiException::class, $exception);
        self::assertSame(500, $exception->httpStatus());
        self::assertSame('INTERNAL_ERROR', $exception->errorCode());
    }

    public function test_falls_back_to_a_generic_message_when_the_body_is_not_the_expected_envelope(): void
    {
        $response = new TransportResponse(500, [], 'not json at all');

        $exception = ErrorMapper::mapToException($response);

        self::assertInstanceOf(ApiException::class, $exception);
        self::assertSame('The API responded with HTTP status 500.', $exception->getMessage());
        self::assertNull($exception->errorCode());
    }
}
