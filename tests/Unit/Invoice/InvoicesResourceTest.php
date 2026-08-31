<?php

declare(strict_types=1);

namespace KBDataSolutions\Sdk\Tests\Unit\Invoice;

use DateTimeImmutable;
use KBDataSolutions\Sdk\Authentication\AccessToken;
use KBDataSolutions\Sdk\Client\ApiClient;
use KBDataSolutions\Sdk\Debtor\DebtorType;
use KBDataSolutions\Sdk\Debtor\Request\CreateDebtorRequest;
use KBDataSolutions\Sdk\Exception\RateLimitException;
use KBDataSolutions\Sdk\Http\TransportResponse;
use KBDataSolutions\Sdk\Invoice\Currency;
use KBDataSolutions\Sdk\Invoice\DecimalAmount;
use KBDataSolutions\Sdk\Invoice\InvoicesResource;
use KBDataSolutions\Sdk\Invoice\Request\CreateInvoiceRequest;
use KBDataSolutions\Sdk\Invoice\Request\DocumentUpload;
use KBDataSolutions\Sdk\Invoice\Request\InvoiceLine;
use KBDataSolutions\Sdk\Invoice\TaxTreatment;
use KBDataSolutions\Sdk\Tests\Fakes\FakeAuthenticator;
use KBDataSolutions\Sdk\Tests\Fakes\FakeSleeper;
use KBDataSolutions\Sdk\Tests\Fakes\FakeTransport;
use PHPUnit\Framework\TestCase;

final class InvoicesResourceTest extends TestCase
{
    private FakeTransport $transport;

    private InvoicesResource $invoices;

    protected function setUp(): void
    {
        $this->transport = new FakeTransport();
        $authenticator = new FakeAuthenticator(new AccessToken('token', new DateTimeImmutable('+1 hour'), []));
        $this->invoices = new InvoicesResource(new ApiClient($this->transport, $authenticator), new FakeSleeper());
    }

    /**
     * @return array<string, mixed>
     */
    private static function responsePayload(string $status = 'pending'): array
    {
        return [
            'data' => [
                'id' => '1',
                'external_invoice_id' => 'wc-order-1042',
                'external_order_id' => null,
                'invoice_number' => null,
                'invoice_date' => '2026-08-01',
                'currency' => 'EUR',
                'tax_treatment' => 'domestic',
                'status' => $status,
                'document_status' => 'none',
                'error_code' => null,
                'error_message' => null,
                'created_at' => '2026-08-31T10:00:00+00:00',
                'updated_at' => '2026-08-31T10:00:00+00:00',
            ],
        ];
    }

    private function makeRequest(?DocumentUpload $document = null): CreateInvoiceRequest
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
            lines: [new InvoiceLine(
                externalId: '1',
                description: 'Widget',
                sku: 'WID-1',
                quantity: '1',
                unitPrice: new DecimalAmount('10.00'),
                netAmount: new DecimalAmount('10.00'),
                vatAmount: new DecimalAmount('2.10'),
                grossAmount: new DecimalAmount('12.10'),
                vatRate: '21',
            )],
            debtor: new CreateDebtorRequest(externalCustomerId: 'cust-1', oneTime: false, type: DebtorType::Consumer, country: 'NL'),
            document: $document,
        );
    }

    public function test_create_sends_a_multipart_request_without_a_document(): void
    {
        $this->transport->queueResponse(new TransportResponse(202, [], json_encode(self::responsePayload(), \JSON_THROW_ON_ERROR)));

        $invoice = $this->invoices->create($this->makeRequest());

        self::assertSame('1', $invoice->id);

        $request = $this->transport->lastRequest();
        self::assertNotNull($request);
        self::assertSame('POST', $request->method);
        self::assertSame('/api/v1/invoices', $request->path);
        self::assertStringStartsWith('multipart/form-data; boundary=', $request->headers['Content-Type']);
        self::assertStringContainsString('name="payload"', (string) $request->body);
        self::assertStringNotContainsString('name="document"', (string) $request->body);
    }

    public function test_create_attaches_the_document_when_given(): void
    {
        $this->transport->queueResponse(new TransportResponse(202, [], json_encode(self::responsePayload(), \JSON_THROW_ON_ERROR)));

        $this->invoices->create($this->makeRequest(new DocumentUpload('invoice.pdf', '%PDF fake')));

        $request = $this->transport->lastRequest();
        self::assertNotNull($request);
        self::assertStringContainsString('name="document"; filename="invoice.pdf"', (string) $request->body);
        self::assertStringContainsString('%PDF fake', (string) $request->body);
    }

    public function test_find_requests_the_correct_path(): void
    {
        $this->transport->queueResponse(new TransportResponse(200, [], json_encode(self::responsePayload('synchronized'), \JSON_THROW_ON_ERROR)));

        $invoice = $this->invoices->find('1');

        self::assertSame('synchronized', $invoice->status->value);

        $request = $this->transport->lastRequest();
        self::assertNotNull($request);
        self::assertSame('GET', $request->method);
        self::assertSame('/api/v1/invoices/1', $request->path);
    }

    public function test_create_throws_rate_limit_exception_on_429(): void
    {
        $this->transport->queueResponse(new TransportResponse(429, ['Retry-After' => '15'], '{"error":{"code":"TOO_MANY_REQUESTS","message":"Too many requests."}}'));

        try {
            $this->invoices->create($this->makeRequest());
            self::fail('Expected a RateLimitException.');
        } catch (RateLimitException $exception) {
            self::assertSame(15, $exception->retryAfterSeconds());
        }
    }
}
