<?php

declare(strict_types=1);

namespace KBDataSolutions\Sdk\Client;

use KBDataSolutions\Sdk\Exception\ApiException;
use KBDataSolutions\Sdk\Exception\AuthenticationException;
use KBDataSolutions\Sdk\Exception\AuthorizationException;
use KBDataSolutions\Sdk\Exception\ConflictException;
use KBDataSolutions\Sdk\Exception\NotFoundException;
use KBDataSolutions\Sdk\Exception\RateLimitException;
use KBDataSolutions\Sdk\Exception\SdkException;
use KBDataSolutions\Sdk\Exception\ValidationException;
use KBDataSolutions\Sdk\Http\TransportResponse;

/**
 * Translates a failed TransportResponse (HTTP status >= 400) from the KB Data Solutions
 * API's JSON error envelope into the corresponding SdkException. The HTTP status code is
 * the stable dimension driving the exception type; the "error.code" string is attached as
 * descriptive data so the mapping stays resilient to the backend introducing new codes.
 */
final class ErrorMapper
{
    public static function mapToException(TransportResponse $response): SdkException
    {
        $error = self::decodeError($response->body);

        $code = \is_string($error['code'] ?? null) ? $error['code'] : null;
        $message = \is_string($error['message'] ?? null) ? $error['message'] : self::defaultMessage($response->statusCode);
        $requestId = $response->requestId();

        return match ($response->statusCode) {
            401 => new AuthenticationException($message, $code, $requestId),
            403 => new AuthorizationException($message, $code, $requestId),
            404 => new NotFoundException($message, $requestId),
            409 => new ConflictException($message, $code, $requestId),
            422 => new ValidationException($message, self::extractFieldErrors($error['details'] ?? null), $code, $requestId),
            429 => new RateLimitException($message, self::retryAfterSeconds($response), $requestId),
            default => new ApiException($message, $response->statusCode, $code, $requestId),
        };
    }

    /**
     * @return array<array-key, mixed>
     */
    private static function decodeError(string $body): array
    {
        $decoded = json_decode($body, true);

        if (!\is_array($decoded) || !\is_array($decoded['error'] ?? null)) {
            return [];
        }

        return $decoded['error'];
    }

    private static function defaultMessage(int $statusCode): string
    {
        return \sprintf('The API responded with HTTP status %d.', $statusCode);
    }

    /**
     * @return array<string, list<string>>
     */
    private static function extractFieldErrors(mixed $details): array
    {
        if (!\is_array($details) || !\is_array($details['fields'] ?? null)) {
            return [];
        }

        $fieldErrors = [];

        foreach ($details['fields'] as $field => $messages) {
            if (!\is_string($field) || !\is_array($messages)) {
                continue;
            }

            /** @var list<string> $stringMessages */
            $stringMessages = array_values(array_filter($messages, static fn (mixed $message): bool => \is_string($message)));

            $fieldErrors[$field] = $stringMessages;
        }

        return $fieldErrors;
    }

    private static function retryAfterSeconds(TransportResponse $response): ?int
    {
        $value = $response->header('Retry-After');

        if ($value === null || !ctype_digit($value)) {
            return null;
        }

        return (int) $value;
    }
}
