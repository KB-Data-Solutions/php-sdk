<?php

declare(strict_types=1);

namespace KBDataSolutions\Sdk\ImportJob\Request;

use InvalidArgumentException;

final readonly class ImportJobCompletion
{
    public const string COMPLETED = 'completed';
    public const string COMPLETED_WITH_ERRORS = 'completed_with_errors';
    public const string FAILED = 'failed';

    public function __construct(
        public string $status,
        public int $ordersSent,
        public int $ordersFailed,
        public int $refundsSent,
        public ?string $lastError = null,
    ) {
        if (!in_array($status, [self::COMPLETED, self::COMPLETED_WITH_ERRORS, self::FAILED], true)) {
            throw new InvalidArgumentException('Unknown import completion status.');
        }
        if ($ordersSent < 0 || $ordersFailed < 0 || $refundsSent < 0) {
            throw new InvalidArgumentException('Import totals cannot be negative.');
        }
    }

    public static function fromTotals(int $ordersSent, int $ordersFailed, int $refundsSent, ?string $lastError = null): self
    {
        $status = $ordersFailed === 0 ? self::COMPLETED : ($ordersSent === 0 ? self::FAILED : self::COMPLETED_WITH_ERRORS);

        return new self($status, $ordersSent, $ordersFailed, $refundsSent, $lastError);
    }

    /** @return array<string, int|string|null> */
    public function toArray(): array
    {
        return [
            'status' => $this->status,
            'orders_sent' => $this->ordersSent,
            'orders_failed' => $this->ordersFailed,
            'refunds_sent' => $this->refundsSent,
            'last_error' => $this->lastError !== null ? mb_substr($this->lastError, 0, 2000) : null,
        ];
    }
}
