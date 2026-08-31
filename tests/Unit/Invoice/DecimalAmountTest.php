<?php

declare(strict_types=1);

namespace KBDataSolutions\Sdk\Tests\Unit\Invoice;

use InvalidArgumentException;
use KBDataSolutions\Sdk\Invoice\DecimalAmount;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class DecimalAmountTest extends TestCase
{
    /**
     * @return iterable<string, array{string}>
     */
    public static function validValues(): iterable
    {
        yield 'integer' => ['10'];
        yield 'two decimals' => ['10.00'];
        yield 'one decimal' => ['10.5'];
        yield 'negative' => ['-10.00'];
        yield 'zero' => ['0.00'];
        yield 'ten digits' => ['1234567890'];
    }

    #[DataProvider('validValues')]
    public function test_accepts_valid_decimal_strings(string $value): void
    {
        self::assertSame($value, (new DecimalAmount($value))->value);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function invalidValues(): iterable
    {
        yield 'three decimals' => ['10.000'];
        yield 'not numeric' => ['abc'];
        yield 'trailing dot' => ['10.'];
        yield 'too many integer digits' => ['12345678901'];
        yield 'comma separator' => ['10,00'];
        yield 'empty string' => [''];
    }

    #[DataProvider('invalidValues')]
    public function test_rejects_invalid_decimal_strings(string $value): void
    {
        $this->expectException(InvalidArgumentException::class);

        new DecimalAmount($value);
    }
}
