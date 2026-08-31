<?php

declare(strict_types=1);

namespace KBDataSolutions\Sdk\Invoice;

use DateTimeImmutable;
use DateTimeZone;
use KBDataSolutions\Sdk\Exception\MappingException;
use KBDataSolutions\Sdk\Support\ArrayShape;

final readonly class Invoice
{
    public function __construct(
        public string $id,
        public string $externalInvoiceId,
        public ?string $externalOrderId,
        public ?string $invoiceNumber,
        public DateTimeImmutable $invoiceDate,
        public Currency $currency,
        public TaxTreatment $taxTreatment,
        public InvoiceStatus $status,
        public string $rawStatus,
        public DocumentStatus $documentStatus,
        public ?InvoiceSyncErrorCode $errorCode,
        public ?string $errorMessage,
        public DateTimeImmutable $createdAt,
        public DateTimeImmutable $updatedAt,
    ) {
    }

    /**
     * @param array<array-key, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        $rawCurrency = ArrayShape::requireString($data, 'currency');
        $currency = Currency::tryFrom($rawCurrency);

        if ($currency === null) {
            throw new MappingException(\sprintf('Field "currency" has an unrecognized value "%s".', $rawCurrency));
        }

        $rawTaxTreatment = ArrayShape::requireString($data, 'tax_treatment');
        $taxTreatment = TaxTreatment::tryFrom($rawTaxTreatment);

        if ($taxTreatment === null) {
            throw new MappingException(\sprintf('Field "tax_treatment" has an unrecognized value "%s".', $rawTaxTreatment));
        }

        $rawStatus = ArrayShape::requireString($data, 'status');
        $rawErrorCode = ArrayShape::optionalString($data, 'error_code');

        return new self(
            ArrayShape::requireString($data, 'id'),
            ArrayShape::requireString($data, 'external_invoice_id'),
            ArrayShape::optionalString($data, 'external_order_id'),
            ArrayShape::optionalString($data, 'invoice_number'),
            self::parseInvoiceDate(ArrayShape::requireString($data, 'invoice_date')),
            $currency,
            $taxTreatment,
            InvoiceStatus::fromApiValue($rawStatus),
            $rawStatus,
            DocumentStatus::fromApiValue(ArrayShape::requireString($data, 'document_status')),
            $rawErrorCode === null ? null : InvoiceSyncErrorCode::fromApiValue($rawErrorCode),
            ArrayShape::optionalString($data, 'error_message'),
            ArrayShape::requireDateTimeImmutable($data, 'created_at'),
            ArrayShape::requireDateTimeImmutable($data, 'updated_at'),
        );
    }

    private static function parseInvoiceDate(string $value): DateTimeImmutable
    {
        $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value, new DateTimeZone('UTC'));

        if ($date === false) {
            throw new MappingException('Field "invoice_date" could not be parsed as a date in "Y-m-d" format.');
        }

        return $date;
    }
}
