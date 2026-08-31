<?php

declare(strict_types=1);

namespace KBDataSolutions\Sdk\Tests\Unit;

use DateTimeImmutable;
use KBDataSolutions\Sdk\Authentication\AccessToken;
use KBDataSolutions\Sdk\Client;
use KBDataSolutions\Sdk\Http\TransportResponse;
use KBDataSolutions\Sdk\Tests\Fakes\FakeAuthenticator;
use KBDataSolutions\Sdk\Tests\Fakes\FakeClock;
use KBDataSolutions\Sdk\Tests\Fakes\FakeSleeper;
use KBDataSolutions\Sdk\Tests\Fakes\FakeTransport;
use PHPUnit\Framework\TestCase;

final class ClientTest extends TestCase
{
    /**
     * @return array<string, mixed>
     */
    private static function debtorPayload(): array
    {
        return [
            'data' => [
                'id' => '1',
                'external_customer_id' => 'cust-1',
                'one_time' => false,
                'type' => 'consumer',
                'status' => 'pending',
                'error_code' => null,
                'error_message' => null,
                'created_at' => '2026-08-31T10:00:00+00:00',
                'updated_at' => '2026-08-31T10:00:00+00:00',
            ],
        ];
    }

    public function test_debtors_and_invoices_return_working_resources_using_the_default_oauth_authenticator(): void
    {
        $transport = new FakeTransport();
        $clock = new FakeClock(new DateTimeImmutable('2026-08-31T10:00:00+00:00'));

        // First call: the default authenticator fetches a token, then the domain call is made.
        $transport->queueResponse(new TransportResponse(200, [], '{"access_token":"token-1","token_type":"Bearer","expires_in":3600}'));
        $transport->queueResponse(new TransportResponse(200, [], json_encode(self::debtorPayload(), \JSON_THROW_ON_ERROR)));

        $client = Client::create('client-id', 'client-secret', 'https://app.kbdatasolutions.nl', transport: $transport, clock: $clock);

        $debtor = $client->debtors()->find('1');

        self::assertSame('1', $debtor->id);
        self::assertCount(2, $transport->sentRequests());
        self::assertSame('/oauth/token', $transport->sentRequests()[0]->path);
        self::assertSame('Bearer token-1', $transport->sentRequests()[1]->headers['Authorization']);
    }

    public function test_the_default_authenticator_caches_the_token_across_calls_within_its_lifetime(): void
    {
        $transport = new FakeTransport();
        $clock = new FakeClock(new DateTimeImmutable('2026-08-31T10:00:00+00:00'));

        $transport->queueResponse(new TransportResponse(200, [], '{"access_token":"token-1","token_type":"Bearer","expires_in":3600}'));
        $transport->queueResponse(new TransportResponse(200, [], json_encode(self::debtorPayload(), \JSON_THROW_ON_ERROR)));
        $transport->queueResponse(new TransportResponse(200, [], json_encode(self::debtorPayload(), \JSON_THROW_ON_ERROR)));

        $client = Client::create('client-id', 'client-secret', 'https://app.kbdatasolutions.nl', transport: $transport, clock: $clock);

        $client->debtors()->find('1');
        $client->debtors()->find('1');

        // Only 1 token request + 2 domain requests = 3, not 4: the second find() reused the cached token.
        self::assertCount(3, $transport->sentRequests());
    }

    public function test_a_custom_authenticator_override_is_used_instead_of_the_default_oauth_flow(): void
    {
        $transport = new FakeTransport();
        $authenticator = new FakeAuthenticator(new AccessToken('custom-token', new DateTimeImmutable('+1 hour'), []));

        $transport->queueResponse(new TransportResponse(200, [], json_encode(self::debtorPayload(), \JSON_THROW_ON_ERROR)));

        $client = Client::create('client-id', 'client-secret', 'https://app.kbdatasolutions.nl', transport: $transport, authenticator: $authenticator);

        $client->debtors()->find('1');

        // No /oauth/token request: the injected authenticator was used directly.
        self::assertCount(1, $transport->sentRequests());
        self::assertSame('Bearer custom-token', $transport->sentRequests()[0]->headers['Authorization']);
    }

    public function test_a_custom_sleeper_override_is_used_by_the_invoices_polling_helper(): void
    {
        $transport = new FakeTransport();
        $sleeper = new FakeSleeper();
        $authenticator = new FakeAuthenticator(new AccessToken('token', new DateTimeImmutable('+1 hour'), []));

        $client = Client::create('client-id', 'client-secret', 'https://app.kbdatasolutions.nl', transport: $transport, authenticator: $authenticator, sleeper: $sleeper);

        $pendingPayload = [
            'data' => [
                'id' => '1', 'external_invoice_id' => 'wc-1', 'external_order_id' => null, 'invoice_number' => null,
                'invoice_date' => '2026-08-01', 'currency' => 'EUR', 'tax_treatment' => 'domestic',
                'status' => 'pending', 'document_status' => 'none', 'error_code' => null, 'error_message' => null,
                'created_at' => '2026-08-31T10:00:00+00:00', 'updated_at' => '2026-08-31T10:00:00+00:00',
            ],
        ];
        $synchronizedPayload = array_merge($pendingPayload, ['data' => array_merge($pendingPayload['data'], ['status' => 'synchronized'])]);

        $transport->queueResponse(new TransportResponse(200, [], json_encode($pendingPayload, \JSON_THROW_ON_ERROR)));
        $transport->queueResponse(new TransportResponse(200, [], json_encode($synchronizedPayload, \JSON_THROW_ON_ERROR)));

        $client->invoices()->waitUntilProcessed('1');

        self::assertNotEmpty($sleeper->sleeps());
    }
}
