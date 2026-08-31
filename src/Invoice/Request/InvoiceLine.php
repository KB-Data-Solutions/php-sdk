<?php

declare(strict_types=1);

namespace KBDataSolutions\Sdk\Invoice\Request;

use InvalidArgumentException;
use KBDataSolutions\Sdk\Invoice\DecimalAmount;

final readonly class InvoiceLine
{
    public function __construct(
        public ?string $externalId,
        public string $description,
        public ?string $sku,
        public string $quantity,
        public DecimalAmount $unitPrice,
        public DecimalAmount $netAmount,
        public DecimalAmount $vatAmount,
        public DecimalAmount $grossAmount,
        public string $vatRate,
    ) {
        if (trim($description) === '') {
            throw new InvalidArgumentException('description must not be empty.');
        }

        if (preg_match('/^\d{1,7}(\.\d{1,4})?$/', $quantity) !== 1 || (float) $quantity <= 0 || (float) $quantity > 1_000_000) {
            throw new InvalidArgumentException(\sprintf('quantity "%s" must be a positive number up to 1,000,000 with at most 4 decimals.', $quantity));
        }

        if (preg_match('/^\d{1,3}(\.\d{1,3})?$/', $vatRate) !== 1 || (float) $vatRate > 100) {
            throw new InvalidArgumentException(\sprintf('vatRate "%s" must be a number between 0 and 100 with at most 3 decimals.', $vatRate));
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'external_id' => $this->externalId,
            'description' => $this->description,
            'sku' => $this->sku,
            'quantity' => $this->quantity,
            'unit_price' => $this->unitPrice->value,
            'net_amount' => $this->netAmount->value,
            'vat_amount' => $this->vatAmount->value,
            'gross_amount' => $this->grossAmount->value,
            'vat_rate' => $this->vatRate,
        ];
    }
}
