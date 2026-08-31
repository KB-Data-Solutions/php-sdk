<?php

declare(strict_types=1);

namespace KBDataSolutions\Sdk\Debtor;

/**
 * Status is a workflow state the backend can extend over time. Unknown is not a real API
 * value; it is the fallback used when the response carries a status this SDK version does
 * not yet recognize, so decoding new backend values never becomes a fatal error.
 */
enum DebtorStatus: string
{
    case Pending = 'pending';
    case Synchronized = 'synchronized';
    case Failed = 'failed';
    case Unknown = 'unknown';

    public static function fromApiValue(string $value): self
    {
        return self::tryFrom($value) ?? self::Unknown;
    }
}
