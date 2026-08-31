<?php

declare(strict_types=1);

namespace KBDataSolutions\Sdk\Invoice;

/**
 * Mirrors KBDataSolutions\Sdk\Debtor\DebtorSyncErrorCode: the backend uses one shared sync
 * error code enum for both debtors and invoices. It is duplicated per module rather than
 * shared from a common namespace so each module stays independently cohesive and free to
 * diverge if the backend ever splits the two error code sets.
 *
 * Unknown is not a real API value; it is the fallback used when the response carries an
 * error code this SDK version does not yet recognize.
 */
enum InvoiceSyncErrorCode: string
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
