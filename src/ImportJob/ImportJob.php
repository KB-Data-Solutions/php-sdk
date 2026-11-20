<?php

declare(strict_types=1);

namespace KBDataSolutions\Sdk\ImportJob;

use KBDataSolutions\Sdk\Support\ArrayShape;

final readonly class ImportJob
{
    public function __construct(
        public string $id,
        public string $status,
        public string $fromDate,
        public string $toDate,
        public ?string $cursor,
        public int $ordersSent,
        public int $ordersFailed,
        public int $refundsSent,
    ) {
    }

    /** @param array<array-key, mixed> $data */
    public static function fromArray(array $data): self
    {
        return new self(
            ArrayShape::requireString($data, 'id'),
            ArrayShape::requireString($data, 'status'),
            ArrayShape::requireString($data, 'from_date'),
            ArrayShape::requireString($data, 'to_date'),
            ArrayShape::optionalString($data, 'cursor'),
            ArrayShape::requireInt($data, 'orders_sent'),
            ArrayShape::requireInt($data, 'orders_failed'),
            ArrayShape::requireInt($data, 'refunds_sent'),
        );
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }
}
