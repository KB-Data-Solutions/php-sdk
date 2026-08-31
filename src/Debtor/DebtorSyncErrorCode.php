<?php

declare(strict_types=1);

namespace KBDataSolutions\Sdk\Debtor;

/**
 * Unknown is not a real API value; it is the fallback used when the response carries an
 * error code this SDK version does not yet recognize.
 */
enum DebtorSyncErrorCode: string
{
    case AccountingSyncFailed = 'ACCOUNTING_SYNC_FAILED';
    case DocumentSyncFailed = 'DOCUMENT_SYNC_FAILED';
    case AdministrationUnavailable = 'ADMINISTRATION_UNAVAILABLE';
    case Unknown = 'unknown';

    public static function fromApiValue(string $value): self
    {
        return self::tryFrom($value) ?? self::Unknown;
    }
}
