<?php

declare(strict_types=1);

namespace KBDataSolutions\Sdk\Tests\Unit\Invoice;

use DateTimeImmutable;
use KBDataSolutions\Sdk\Authentication\AccessToken;
use KBDataSolutions\Sdk\Client\ApiClient;
use KBDataSolutions\Sdk\Http\TransportResponse;
use KBDataSolutions\Sdk\Invoice\InvoicePollingTimeoutException;
use KBDataSolutions\Sdk\Invoice\InvoicesResource;
use KBDataSolutions\Sdk\Invoice\PollingOptions;
use KBDataSolutions\Sdk\Tests\Fakes\FakeAuthenticator;
use KBDataSolutions\Sdk\Tests\Fakes\FakeSleeper;
use KBDataSolutions\Sdk\Tests\Fakes\FakeTransport;
use PHPUnit\Framework\TestCase;

final class InvoicesResourceWaitUntilProcessedTest extends TestCase
{
    private FakeTransport $transport;

    private FakeSleeper $sleeper;

    private InvoicesResource $invoices;

    protected function setUp(): void
    {
        $this->transport = new FakeTransport();
        $this->sleeper = new FakeSleeper();
        $authenticator = new FakeAuthenticator(new AccessToken('token', new DateTimeImmutable('+1 hour'), []));
        $this->invoices = new InvoicesResource(new ApiClient($this->transport, $authenticator), $this->sleeper);
    }

    /**
     * @return array<string, mixed>
     */
    private static function responsePayload(string $status): array
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

    private function queue(string $status): void
    {
        $this->transport->queueResponse(new TransportResponse(200, [], json_encode(self::responsePayload($status), \JSON_THROW_ON_ERROR)));
    }

    public function test_returns_immediately_when_already_terminal(): void
    {
        $this->queue('synchronized');

        $invoice = $this->invoices->waitUntilProcessed('1');

        self::assertSame('synchronized', $invoice->status->value);
        self::assertCount(1, $this->transport->sentRequests());
        self::assertSame([], $this->sleeper->sleeps());
    }

    public function test_polls_until_a_terminal_status_is_reached(): void
    {
        $this->queue('pending');
        $this->queue('processing');
        $this->queue('synchronized');

        $invoice = $this->invoices->waitUntilProcessed('1', new PollingOptions(maxAttempts: 5, intervalMilliseconds: 100));

        self::assertSame('synchronized', $invoice->status->value);
        self::assertCount(3, $this->transport->sentRequests());
        self::assertSame([100, 100], $this->sleeper->sleeps());
    }

    public function test_applies_backoff_multiplier_between_attempts(): void
    {
        $this->queue('pending');
        $this->queue('pending');
        $this->queue('synchronized');

        $this->invoices->waitUntilProcessed('1', new PollingOptions(maxAttempts: 5, intervalMilliseconds: 100, backoffMultiplier: 2.0));

        self::assertSame([100, 200], $this->sleeper->sleeps());
    }

    public function test_throws_polling_timeout_exception_after_max_attempts_exhausted(): void
    {
        $this->queue('pending');
        $this->queue('pending');
        $this->queue('pending');

        try {
            $this->invoices->waitUntilProcessed('1', new PollingOptions(maxAttempts: 3, intervalMilliseconds: 10));
            self::fail('Expected an InvoicePollingTimeoutException.');
        } catch (InvoicePollingTimeoutException $exception) {
            self::assertSame(3, $exception->attemptsMade());
            self::assertSame('pending', $exception->lastKnownInvoice()->status->value);
        }

        self::assertCount(3, $this->transport->sentRequests());
        self::assertCount(2, $this->sleeper->sleeps());
    }

    public function test_a_single_max_attempt_checks_once_without_sleeping(): void
    {
        $this->queue('pending');

        try {
            $this->invoices->waitUntilProcessed('1', new PollingOptions(maxAttempts: 1));
            self::fail('Expected an InvoicePollingTimeoutException.');
        } catch (InvoicePollingTimeoutException $exception) {
            self::assertSame(1, $exception->attemptsMade());
        }

        self::assertCount(1, $this->transport->sentRequests());
        self::assertSame([], $this->sleeper->sleeps());
    }
}
