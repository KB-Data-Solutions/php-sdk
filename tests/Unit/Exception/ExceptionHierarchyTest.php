<?php

declare(strict_types=1);

namespace KBDataSolutions\Sdk\Tests\Unit\Exception;

use KBDataSolutions\Sdk\Exception\ApiException;
use KBDataSolutions\Sdk\Exception\AuthenticationException;
use KBDataSolutions\Sdk\Exception\AuthorizationException;
use KBDataSolutions\Sdk\Exception\ConflictException;
use KBDataSolutions\Sdk\Exception\MappingException;
use KBDataSolutions\Sdk\Exception\NotFoundException;
use KBDataSolutions\Sdk\Exception\RateLimitException;
use KBDataSolutions\Sdk\Exception\SdkException;
use KBDataSolutions\Sdk\Exception\TransportException;
use KBDataSolutions\Sdk\Exception\ValidationException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class ExceptionHierarchyTest extends TestCase
{
    /**
     * @return iterable<string, array{class-string<SdkException>}>
     */
    public static function sdkExceptionClasses(): iterable
    {
        yield AuthenticationException::class => [AuthenticationException::class];
        yield AuthorizationException::class => [AuthorizationException::class];
        yield ValidationException::class => [ValidationException::class];
        yield NotFoundException::class => [NotFoundException::class];
        yield ConflictException::class => [ConflictException::class];
        yield RateLimitException::class => [RateLimitException::class];
        yield ApiException::class => [ApiException::class];
        yield TransportException::class => [TransportException::class];
        yield MappingException::class => [MappingException::class];
    }

    /**
     * @param class-string<SdkException> $class
     */
    #[DataProvider('sdkExceptionClasses')]
    public function test_every_sdk_exception_implements_marker_interface_and_extends_runtime_exception(string $class): void
    {
        self::assertTrue(is_a($class, SdkException::class, true));
        self::assertTrue(is_a($class, RuntimeException::class, true));
    }

    public function test_authentication_exception_carries_error_code_and_request_id(): void
    {
        $exception = new AuthenticationException('Unauthenticated.', 'UNAUTHENTICATED', 'req-1');

        self::assertSame('Unauthenticated.', $exception->getMessage());
        self::assertSame('UNAUTHENTICATED', $exception->errorCode());
        self::assertSame('req-1', $exception->requestId());
    }

    public function test_authorization_exception_carries_error_code_and_request_id(): void
    {
        $exception = new AuthorizationException('Forbidden.', 'SUBSCRIPTION_INACTIVE', 'req-2');

        self::assertSame('SUBSCRIPTION_INACTIVE', $exception->errorCode());
        self::assertSame('req-2', $exception->requestId());
    }

    public function test_validation_exception_carries_field_errors(): void
    {
        $exception = new ValidationException(
            'The given data was invalid.',
            ['type' => ['The type field is required.']],
            'VALIDATION_FAILED',
            'req-3',
        );

        self::assertSame(['type' => ['The type field is required.']], $exception->fieldErrors());
        self::assertSame('VALIDATION_FAILED', $exception->errorCode());
        self::assertSame('req-3', $exception->requestId());
    }

    public function test_not_found_exception_carries_only_request_id(): void
    {
        $exception = new NotFoundException('Not found.', 'req-4');

        self::assertSame('req-4', $exception->requestId());
    }

    public function test_conflict_exception_carries_error_code_and_request_id(): void
    {
        $exception = new ConflictException('Conflict.', 'IDEMPOTENCY_KEY_REUSED', 'req-5');

        self::assertSame('IDEMPOTENCY_KEY_REUSED', $exception->errorCode());
        self::assertSame('req-5', $exception->requestId());
    }

    public function test_rate_limit_exception_carries_retry_after_and_request_id(): void
    {
        $exception = new RateLimitException('Too many requests.', 30, 'req-6');

        self::assertSame(30, $exception->retryAfterSeconds());
        self::assertSame('req-6', $exception->requestId());
    }

    public function test_api_exception_carries_http_status_error_code_and_request_id(): void
    {
        $exception = new ApiException('Internal error.', 500, 'INTERNAL_ERROR', 'req-7');

        self::assertSame(500, $exception->httpStatus());
        self::assertSame('INTERNAL_ERROR', $exception->errorCode());
        self::assertSame('req-7', $exception->requestId());
    }

    public function test_transport_exception_preserves_previous_exception(): void
    {
        $previous = new RuntimeException('Connection refused.');
        $exception = new TransportException('Request failed.', $previous);

        self::assertSame($previous, $exception->getPrevious());
    }

    public function test_mapping_exception_carries_message_only(): void
    {
        $exception = new MappingException('Field "id" must be of type string, int given.');

        self::assertSame('Field "id" must be of type string, int given.', $exception->getMessage());
    }
}
