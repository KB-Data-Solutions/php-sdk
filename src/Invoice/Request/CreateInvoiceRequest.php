<?php

declare(strict_types=1);

namespace KBDataSolutions\Sdk\Invoice\Request;

use DateTimeImmutable;
use InvalidArgumentException;
use KBDataSolutions\Sdk\Debtor\Request\CreateDebtorRequest;
use KBDataSolutions\Sdk\Invoice\Currency;
use KBDataSolutions\Sdk\Invoice\DecimalAmount;
use KBDataSolutions\Sdk\Invoice\TaxTreatment;

final readonly class CreateInvoiceRequest
{
    private const MAX_LINES = 500;

    /**
     * @param list<InvoiceLine> $lines
     */
    public function __construct(
        public string $externalInvoiceId,
        public DateTimeImmutable $invoiceDate,
        public Currency $currency,
        public TaxTreatment $taxTreatment,
        public string $destinationCountry,
        public DecimalAmount $netAmount,
        public DecimalAmount $vatAmount,
        public DecimalAmount $grossAmount,
        public array $lines,
        public CreateDebtorRequest $debtor,
        public ?string $externalOrderId = null,
        public ?string $invoiceNumber = null,
        public ?DocumentUpload $document = null,
    ) {
        if (trim($externalInvoiceId) === '') {
            throw new InvalidArgumentException('externalInvoiceId must not be empty.');
        }

        if (trim($destinationCountry) === '') {
            throw new InvalidArgumentException('destinationCountry must not be empty.');
        }

        $lineCount = \count($lines);

        if ($lineCount < 1 || $lineCount > self::MAX_LINES) {
            throw new InvalidArgumentException(\sprintf('lines must contain between 1 and %d items, %d given.', self::MAX_LINES, $lineCount));
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'external_invoice_id' => $this->externalInvoiceId,
            'external_order_id' => $this->externalOrderId,
            'invoice_number' => $this->invoiceNumber,
            'invoice_date' => $this->invoiceDate->format('Y-m-d'),
            'currency' => $this->currency->value,
            'tax_treatment' => $this->taxTreatment->value,
            'destination_country' => $this->destinationCountry,
            'net_amount' => $this->netAmount->value,
            'vat_amount' => $this->vatAmount->value,
            'gross_amount' => $this->grossAmount->value,
            'lines' => array_map(static fn (InvoiceLine $line): array => $line->toArray(), $this->lines),
            'debtor' => $this->debtor->toArray(),
        ];
    }
}
