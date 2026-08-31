<?php

declare(strict_types=1);

namespace KBDataSolutions\Sdk\Invoice;

use KBDataSolutions\Sdk\Exception\SdkException;
use RuntimeException;

final class InvoicePollingTimeoutException extends RuntimeException implements SdkException
{
    public function __construct(
        string $message,
        private readonly Invoice $lastKnownInvoice,
        private readonly int $attemptsMade,
    ) {
        parent::__construct($message);
    }

    public function lastKnownInvoice(): Invoice
    {
        return $this->lastKnownInvoice;
    }

    public function attemptsMade(): int
    {
        return $this->attemptsMade;
    }
}
