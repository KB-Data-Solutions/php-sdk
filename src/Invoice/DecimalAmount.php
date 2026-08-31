<?php

declare(strict_types=1);

namespace KBDataSolutions\Sdk\Invoice;

use InvalidArgumentException;

/**
 * The API represents money as decimal strings, never floats, to avoid rounding surprises.
 * This value object centralizes the wire-format check shared by every money field
 * (net/vat/gross amount, unit price).
 */
final readonly class DecimalAmount
{
    private const PATTERN = '/^-?\d{1,10}(\.\d{1,2})?$/';

    public string $value;

    public function __construct(string $value)
    {
        if (preg_match(self::PATTERN, $value) !== 1) {
            throw new InvalidArgumentException(\sprintf(
                'Amount "%s" is not a valid decimal string (expected pattern %s).',
                $value,
                self::PATTERN,
            ));
        }

        $this->value = $value;
    }
}
