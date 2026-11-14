<?php

declare(strict_types=1);

namespace KBDataSolutions\Sdk\Payment;

use KBDataSolutions\Sdk\Support\ArrayShape;

final readonly class Payment
{
    public function __construct(public string $id, public string $externalPaymentId, public string $rawStatus, public ?string $errorCode)
    {
    }

    /** @param array<array-key, mixed> $data */
    public static function fromArray(array $data): self
    {
        return new self(
            ArrayShape::requireString($data, 'id'),
            ArrayShape::requireString($data, 'external_payment_id'),
            ArrayShape::requireString($data, 'status'),
            ArrayShape::optionalString($data, 'error_code')
        );
    }
}
