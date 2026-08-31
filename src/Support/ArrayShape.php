<?php

declare(strict_types=1);

namespace KBDataSolutions\Sdk\Support;

use DateTimeImmutable;
use DateTimeZone;
use KBDataSolutions\Sdk\Exception\MappingException;

/**
 * @internal Shared validation helpers used by response DTOs' fromArray() constructors.
 */
final class ArrayShape
{
    /**
     * @param array<array-key, mixed> $data
     */
    public static function requireString(array $data, string $field): string
    {
        $value = $data[$field] ?? null;

        if (!\is_string($value)) {
            throw self::mismatch($field, 'string', $value);
        }

        return $value;
    }

    /**
     * @param array<array-key, mixed> $data
     */
    public static function optionalString(array $data, string $field): ?string
    {
        $value = $data[$field] ?? null;

        if ($value === null) {
            return null;
        }

        if (!\is_string($value)) {
            throw self::mismatch($field, 'string or null', $value);
        }

        return $value;
    }

    /**
     * @param array<array-key, mixed> $data
     */
    public static function requireBool(array $data, string $field): bool
    {
        $value = $data[$field] ?? null;

        if (!\is_bool($value)) {
            throw self::mismatch($field, 'bool', $value);
        }

        return $value;
    }

    /**
     * @param array<array-key, mixed> $data
     */
    public static function requireDateTimeImmutable(array $data, string $field): DateTimeImmutable
    {
        $value = self::requireString($data, $field);
        $date = DateTimeImmutable::createFromFormat(DATE_ATOM, $value, new DateTimeZone('UTC'));

        if ($date === false) {
            throw new MappingException(\sprintf(
                'Field "%s" could not be parsed as an ISO 8601 datetime.',
                $field,
            ));
        }

        return $date;
    }

    /**
     * @param array<array-key, mixed> $data
     * @return array<array-key, mixed>
     */
    public static function requireArray(array $data, string $field): array
    {
        $value = $data[$field] ?? null;

        if (!\is_array($value)) {
            throw self::mismatch($field, 'array', $value);
        }

        return $value;
    }

    private static function mismatch(string $field, string $expectedType, mixed $value): MappingException
    {
        return new MappingException(\sprintf(
            'Field "%s" must be of type %s, %s given.',
            $field,
            $expectedType,
            \get_debug_type($value),
        ));
    }
}
