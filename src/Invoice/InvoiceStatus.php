<?php

declare(strict_types=1);

namespace KBDataSolutions\Sdk\Invoice;

/**
 * Unknown is not a real API value; it is the fallback used when the response carries a
 * status this SDK version does not yet recognize.
 */
enum InvoiceStatus: string
{
    case Pending = 'pending';
    case Processing = 'processing';
    case Synchronized = 'synchronized';
    case Failed = 'failed';
    case DocumentFailed = 'document_failed';
    case Unknown = 'unknown';

    public static function fromApiValue(string $value): self
    {
        return self::tryFrom($value) ?? self::Unknown;
    }

    public function isTerminal(): bool
    {
        return match ($this) {
            self::Synchronized, self::Failed, self::DocumentFailed => true,
            self::Pending, self::Processing, self::Unknown => false,
        };
    }
}
