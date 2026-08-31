<?php

declare(strict_types=1);

namespace KBDataSolutions\Sdk\Tests\Unit\Invoice\Request;

use DateTimeImmutable;
use InvalidArgumentException;
use KBDataSolutions\Sdk\Debtor\DebtorType;
use KBDataSolutions\Sdk\Debtor\Request\CreateDebtorRequest;
use KBDataSolutions\Sdk\Invoice\Currency;
use KBDataSolutions\Sdk\Invoice\DecimalAmount;
use KBDataSolutions\Sdk\Invoice\Request\CreateInvoiceRequest;
use KBDataSolutions\Sdk\Invoice\Request\DocumentUpload;
use KBDataSolutions\Sdk\Invoice\Request\InvoiceLine;
use KBDataSolutions\Sdk\Invoice\TaxTreatment;
use PHPUnit\Framework\TestCase;

final class CreateInvoiceRequestTest extends TestCase
{
    /**
     * @param list<InvoiceLine> $lines
     */
    private function makeRequest(array $lines): CreateInvoiceRequest
    {
        return new CreateInvoiceRequest(
            externalInvoiceId: 'wc-order-1042',
            invoiceDate: new DateTimeImmutable('2026-08-01'),
            currency: Currency::Eur,
            taxTreatment: TaxTreatment::Domestic,
            destinationCountry: 'NL',
            netAmount: new DecimalAmount('10.00'),
            vatAmount: new DecimalAmount('2.10'),
            grossAmount: new DecimalAmount('12.10'),
            lines: $lines,
            debtor: new CreateDebtorRequest(
                externalCustomerId: 'cust-1',
                oneTime: false,
                type: DebtorType::Consumer,
                country: 'NL',
            ),
        );
    }

    private function makeLine(): InvoiceLine
    {
        return new InvoiceLine(
            externalId: '1',
            description: 'Widget',
            sku: 'WID-1',
            quantity: '1',
            unitPrice: new DecimalAmount('10.00'),
            netAmount: new DecimalAmount('10.00'),
            vatAmount: new DecimalAmount('2.10'),
            grossAmount: new DecimalAmount('12.10'),
            vatRate: '21',
        );
    }

    public function test_to_array_maps_all_fields_including_the_nested_debtor_and_lines(): void
    {
        $result = $this->makeRequest([$this->makeLine()])->toArray();

        self::assertSame('wc-order-1042', $result['external_invoice_id']);
        self::assertSame('2026-08-01', $result['invoice_date']);
        self::assertSame('EUR', $result['currency']);
        self::assertSame('domestic', $result['tax_treatment']);
        self::assertIsArray($result['lines']);
        self::assertCount(1, $result['lines']);
        self::assertIsArray($result['debtor']);
        self::assertSame('consumer', $result['debtor']['type']);
    }

    public function test_external_invoice_id_must_not_be_empty(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new CreateInvoiceRequest(
            externalInvoiceId: '',
            invoiceDate: new DateTimeImmutable('2026-08-01'),
            currency: Currency::Eur,
            taxTreatment: TaxTreatment::Domestic,
            destinationCountry: 'NL',
            netAmount: new DecimalAmount('10.00'),
            vatAmount: new DecimalAmount('2.10'),
            grossAmount: new DecimalAmount('12.10'),
            lines: [$this->makeLine()],
            debtor: new CreateDebtorRequest(externalCustomerId: 'cust-1', oneTime: false, type: DebtorType::Consumer, country: 'NL'),
        );
    }

    public function test_lines_must_not_be_empty(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->makeRequest([]);
    }

    public function test_lines_must_not_exceed_500_items(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->makeRequest(array_fill(0, 501, $this->makeLine()));
    }

    public function test_document_defaults_to_null(): void
    {
        self::assertNull($this->makeRequest([$this->makeLine()])->document);
    }

    public function test_document_can_be_attached(): void
    {
        $document = new DocumentUpload('invoice.pdf', 'fake pdf contents');
        $request = new CreateInvoiceRequest(
            externalInvoiceId: 'wc-order-1042',
            invoiceDate: new DateTimeImmutable('2026-08-01'),
            currency: Currency::Eur,
            taxTreatment: TaxTreatment::Domestic,
            destinationCountry: 'NL',
            netAmount: new DecimalAmount('10.00'),
            vatAmount: new DecimalAmount('2.10'),
            grossAmount: new DecimalAmount('12.10'),
            lines: [$this->makeLine()],
            debtor: new CreateDebtorRequest(externalCustomerId: 'cust-1', oneTime: false, type: DebtorType::Consumer, country: 'NL'),
            document: $document,
        );

        self::assertSame($document, $request->document);
    }
}
