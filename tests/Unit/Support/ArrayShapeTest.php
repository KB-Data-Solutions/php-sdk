<?php

declare(strict_types=1);

namespace KBDataSolutions\Sdk\Tests\Unit\Support;

use KBDataSolutions\Sdk\Exception\MappingException;
use KBDataSolutions\Sdk\Support\ArrayShape;
use PHPUnit\Framework\TestCase;

final class ArrayShapeTest extends TestCase
{
    public function test_require_string_returns_string_value(): void
    {
        self::assertSame('abc', ArrayShape::requireString(['field' => 'abc'], 'field'));
    }

    public function test_require_string_throws_on_missing_field(): void
    {
        $this->expectException(MappingException::class);
        $this->expectExceptionMessage('Field "field" must be of type string, null given.');

        ArrayShape::requireString([], 'field');
    }

    public function test_require_string_throws_on_wrong_type(): void
    {
        $this->expectException(MappingException::class);

        ArrayShape::requireString(['field' => 123], 'field');
    }

    public function test_optional_string_returns_null_when_absent(): void
    {
        self::assertNull(ArrayShape::optionalString([], 'field'));
    }

    public function test_optional_string_returns_null_when_explicitly_null(): void
    {
        self::assertNull(ArrayShape::optionalString(['field' => null], 'field'));
    }

    public function test_optional_string_returns_value_when_present(): void
    {
        self::assertSame('abc', ArrayShape::optionalString(['field' => 'abc'], 'field'));
    }

    public function test_optional_string_throws_on_wrong_type(): void
    {
        $this->expectException(MappingException::class);

        ArrayShape::optionalString(['field' => 123], 'field');
    }

    public function test_require_bool_returns_bool_value(): void
    {
        self::assertTrue(ArrayShape::requireBool(['field' => true], 'field'));
        self::assertFalse(ArrayShape::requireBool(['field' => false], 'field'));
    }

    public function test_require_bool_throws_on_wrong_type(): void
    {
        $this->expectException(MappingException::class);

        ArrayShape::requireBool(['field' => 'true'], 'field');
    }

    public function test_require_date_time_immutable_parses_iso8601_utc(): void
    {
        $date = ArrayShape::requireDateTimeImmutable(['field' => '2026-08-31T10:00:00+00:00'], 'field');

        self::assertSame('2026-08-31T10:00:00+00:00', $date->format(DATE_ATOM));
    }

    public function test_require_date_time_immutable_throws_on_unparsable_value(): void
    {
        $this->expectException(MappingException::class);

        ArrayShape::requireDateTimeImmutable(['field' => 'not-a-date'], 'field');
    }

    public function test_require_array_returns_array_value(): void
    {
        self::assertSame(['a' => 1], ArrayShape::requireArray(['field' => ['a' => 1]], 'field'));
    }

    public function test_require_array_throws_on_wrong_type(): void
    {
        $this->expectException(MappingException::class);

        ArrayShape::requireArray(['field' => 'not-an-array'], 'field');
    }
}
