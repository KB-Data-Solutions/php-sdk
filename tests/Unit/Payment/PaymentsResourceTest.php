<?php

declare(strict_types=1);

namespace KBDataSolutions\Sdk\Tests\Unit\Payment;

use DateTimeImmutable;
use KBDataSolutions\Sdk\Authentication\AccessToken;
use KBDataSolutions\Sdk\Client;
use KBDataSolutions\Sdk\Http\TransportResponse;
use KBDataSolutions\Sdk\Invoice\DecimalAmount;
use KBDataSolutions\Sdk\Payment\Request\CreatePaymentRequest;
use KBDataSolutions\Sdk\PaymentMethod\PaymentMethod;
use KBDataSolutions\Sdk\Tests\Fakes\FakeAuthenticator;
use KBDataSolutions\Sdk\Tests\Fakes\FakeTransport;
use PHPUnit\Framework\TestCase;

final class PaymentsResourceTest extends TestCase
{
    public function test_catalogue_and_payment_use_separate_requests_and_existing_authentication(): void
    {
        $transport = new FakeTransport();
        $client = Client::create(
            'existing-id',
            'existing-secret',
            'https://api.example.test',
            'woocommerce',
            transport: $transport,
            authenticator: new FakeAuthenticator(new AccessToken('existing-token', new DateTimeImmutable('+1 hour'), []))
        );
        $transport->queueResponse(new TransportResponse(200, [], '{"data":{"methods":[{"key":"custom","label":"Mijn betaalmethode","provider":null,"enabled":true}]}}'));
        $methods = $client->paymentMethods()->sync([new PaymentMethod('custom', 'Mijn betaalmethode')], 'catalogue-1');
        self::assertSame('Mijn betaalmethode', $methods[0]->label);
        $request = $transport->lastRequest();
        self::assertNotNull($request);
        self::assertSame('/api/v1/payment-methods', $request->path);
        self::assertSame('Bearer existing-token', $request->headers['Authorization']);
        self::assertSame('catalogue-1', $request->headers['Idempotency-Key']);

        $transport->queueResponse(new TransportResponse(202, [], '{"data":{"id":"7","external_payment_id":"paid-1","status":"pending","error_code":null}}'));
        $payment = $client->payments()->create(new CreatePaymentRequest(
            'paid-1',
            'wc-order-1',
            'custom',
            'txn-1',
            new DecimalAmount('12.1'),
            new DateTimeImmutable('2026-01-01T12:00:00+00:00')
        ), 'payment-1');
        self::assertSame('7', $payment->id);
        $request = $transport->lastRequest();
        self::assertNotNull($request);
        self::assertSame('/api/v1/payments', $request->path);
        self::assertSame('payment-1', $request->headers['Idempotency-Key']);
        $body = json_decode((string) $request->body, true, 512, JSON_THROW_ON_ERROR);
        self::assertIsArray($body);
        self::assertSame('12.10', $body['amount']);
        self::assertSame('confirmed', $body['status']);
        self::assertArrayNotHasKey('methods', $body);
    }

    public function test_unconfirmed_zero_amount_is_rejected_before_a_request(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new CreatePaymentRequest('p', 'i', 'm', 't', new DecimalAmount('0.00'), new DateTimeImmutable());
    }
}
