<?php

declare(strict_types=1);

namespace KBDataSolutions\Sdk\Tests\Unit\Invoice;

use KBDataSolutions\Sdk\Exception\MappingException;
use KBDataSolutions\Sdk\Invoice\Currency;
use KBDataSolutions\Sdk\Invoice\DocumentStatus;
use KBDataSolutions\Sdk\Invoice\Invoice;
use KBDataSolutions\Sdk\Invoice\InvoiceStatus;
use KBDataSolutions\Sdk\Invoice\InvoiceSyncErrorCode;
use KBDataSolutions\Sdk\Invoice\TaxTreatment;
use PHPUnit\Framework\TestCase;

final class InvoiceTest extends TestCase
{
    /**
     * @return array<string, mixed>
     */
    private static function validPayload(): array
    {
        return [
            'id' => '456',
            'external_invoice_id' => 'wc-order-1042',
            'external_order_id' => '1042',
            'invoice_number' => null,
            'invoice_date' => '2026-08-01',
            'currency' => 'EUR',
            'tax_treatment' => 'domestic',
            'status' => 'pending',
            'document_status' => 'none',
            'error_code' => null,
            'error_message' => null,
            'created_at' => '2026-08-31T10:00:00+00:00',
            'updated_at' => '2026-08-31T10:00:00+00:00',
        ];
    }

    public function test_from_array_maps_a_valid_payload(): void
    {
        $invoice = Invoice::fromArray(self::validPayload());

        self::assertSame('456', $invoice->id);
        self::assertSame('wc-order-1042', $invoice->externalInvoiceId);
        self::assertSame('1042', $invoice->externalOrderId);
        self::assertNull($invoice->invoiceNumber);
        self::assertSame('2026-08-01', $invoice->invoiceDate->format('Y-m-d'));
        self::assertSame(Currency::Eur, $invoice->currency);
        self::assertSame(TaxTreatment::Domestic, $invoice->taxTreatment);
        self::assertSame(InvoiceStatus::Pending, $invoice->status);
        self::assertSame(DocumentStatus::None, $invoice->documentStatus);
        self::assertNull($invoice->errorCode);
    }

    public function test_from_array_maps_a_failed_invoice_with_an_error_code(): void
    {
        $payload = array_merge(self::validPayload(), [
            'status' => 'failed',
            'error_code' => 'DOCUMENT_SYNC_FAILED',
            'error_message' => 'The document could not be synced.',
        ]);

        $invoice = Invoice::fromArray($payload);

        self::assertSame(InvoiceStatus::Failed, $invoice->status);
        self::assertSame(InvoiceSyncErrorCode::DocumentSyncFailed, $invoice->errorCode);
    }

    public function test_from_array_falls_back_to_unknown_for_an_unrecognized_status(): void
    {
        $payload = array_merge(self::validPayload(), ['status' => 'a_future_status']);

        $invoice = Invoice::fromArray($payload);

        self::assertSame(InvoiceStatus::Unknown, $invoice->status);
        self::assertFalse($invoice->status->isTerminal());
    }

    public function test_terminal_statuses_are_recognized(): void
    {
        foreach (['synchronized', 'failed', 'document_failed'] as $terminalStatus) {
            $payload = array_merge(self::validPayload(), ['status' => $terminalStatus]);

            self::assertTrue(Invoice::fromArray($payload)->status->isTerminal());
        }
    }

    public function test_non_terminal_statuses_are_recognized(): void
    {
        foreach (['pending', 'processing'] as $nonTerminalStatus) {
            $payload = array_merge(self::validPayload(), ['status' => $nonTerminalStatus]);

            self::assertFalse(Invoice::fromArray($payload)->status->isTerminal());
        }
    }

    public function test_from_array_throws_a_mapping_exception_for_an_unrecognized_currency(): void
    {
        $payload = array_merge(self::validPayload(), ['currency' => 'USD']);

        $this->expectException(MappingException::class);

        Invoice::fromArray($payload);
    }

    public function test_from_array_throws_a_mapping_exception_for_an_unparsable_invoice_date(): void
    {
        $payload = array_merge(self::validPayload(), ['invoice_date' => 'not-a-date']);

        $this->expectException(MappingException::class);

        Invoice::fromArray($payload);
    }
}
