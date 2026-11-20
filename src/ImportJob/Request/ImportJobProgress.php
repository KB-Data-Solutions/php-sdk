<?php

declare(strict_types=1);

namespace KBDataSolutions\Sdk\ImportJob\Request;

use InvalidArgumentException;

final readonly class ImportJobProgress
{
    public function __construct(
        public ?string $cursor,
        public int $ordersSent,
        public int $ordersFailed,
        public int $refundsSent,
        public ?string $lastError = null,
    ) {
        if ($ordersSent < 0 || $ordersFailed < 0 || $refundsSent < 0) {
            throw new InvalidArgumentException('Import totals cannot be negative.');
        }
        if ($cursor !== null && ($cursor === '' || strlen($cursor) > 64)) {
            throw new InvalidArgumentException('An import cursor must contain 1 to 64 characters.');
        }
    }

    /** @return array<string, int|string|null> */
    public function toArray(): array
    {
        return [
            'cursor' => $this->cursor,
            'orders_sent' => $this->ordersSent,
            'orders_failed' => $this->ordersFailed,
            'refunds_sent' => $this->refundsSent,
            'last_error' => $this->lastError !== null ? mb_substr($this->lastError, 0, 2000) : null,
        ];
    }
}
