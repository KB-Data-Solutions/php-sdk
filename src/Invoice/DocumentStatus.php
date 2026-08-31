<?php

declare(strict_types=1);

namespace KBDataSolutions\Sdk\Invoice;

/**
 * Unknown is not a real API value; it is the fallback used when the response carries a
 * document status this SDK version does not yet recognize.
 */
enum DocumentStatus: string
{
    case None = 'none';
    case Pending = 'pending';
    case Uploaded = 'uploaded';
    case Failed = 'failed';
    case Unknown = 'unknown';

    public static function fromApiValue(string $value): self
    {
        return self::tryFrom($value) ?? self::Unknown;
    }
}
