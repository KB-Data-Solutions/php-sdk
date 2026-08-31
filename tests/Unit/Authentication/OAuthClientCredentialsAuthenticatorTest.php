<?php

declare(strict_types=1);

namespace KBDataSolutions\Sdk\Tests\Unit\Authentication;

use DateTimeImmutable;
use KBDataSolutions\Sdk\Authentication\Credentials;
use KBDataSolutions\Sdk\Authentication\InMemoryTokenCache;
use KBDataSolutions\Sdk\Authentication\OAuthClientCredentialsAuthenticator;
use KBDataSolutions\Sdk\Authentication\Scope;
use KBDataSolutions\Sdk\Exception\AuthenticationException;
use KBDataSolutions\Sdk\Http\TransportResponse;
use KBDataSolutions\Sdk\Tests\Fakes\FakeClock;
use KBDataSolutions\Sdk\Tests\Fakes\FakeTransport;
use PHPUnit\Framework\TestCase;

final class OAuthClientCredentialsAuthenticatorTest extends TestCase
{
    private FakeTransport $transport;

    private FakeClock $clock;

    private InMemoryTokenCache $tokenCache;

    private OAuthClientCredentialsAuthenticator $authenticator;

    protected function setUp(): void
    {
        $this->transport = new FakeTransport();
        $this->clock = new FakeClock(new DateTimeImmutable('2026-08-31T10:00:00+00:00'));
        $this->tokenCache = new InMemoryTokenCache();
        $this->authenticator = new OAuthClientCredentialsAuthenticator(
            $this->transport,
            new Credentials('client-id', 'client-secret'),
            $this->tokenCache,
            $this->clock,
            [Scope::DebtorsWrite, Scope::InvoicesWrite],
        );
    }

    public function test_access_token_fetches_and_caches_a_new_token(): void
    {
        $this->transport->queueResponse(new TransportResponse(200, [], '{"access_token":"token-1","token_type":"Bearer","expires_in":3600}'));

        $token = $this->authenticator->accessToken();

        self::assertSame('token-1', $token->token);
        self::assertSame($this->tokenCache->get(), $token);

        $request = $this->transport->lastRequest();
        self::assertNotNull($request);
        self::assertSame('/oauth/token', $request->path);
        self::assertSame('application/x-www-form-urlencoded', $request->headers['Content-Type']);
        parse_str((string) $request->body, $sentFields);
        self::assertSame('client_credentials', $sentFields['grant_type']);
        self::assertSame('client-id', $sentFields['client_id']);
        self::assertSame('client-secret', $sentFields['client_secret']);
        self::assertSame('debtors:write invoices:write', $sentFields['scope']);
    }

    public function test_access_token_returns_the_cached_token_without_a_new_request(): void
    {
        $this->transport->queueResponse(new TransportResponse(200, [], '{"access_token":"token-1","token_type":"Bearer","expires_in":3600}'));
        $this->authenticator->accessToken();

        $token = $this->authenticator->accessToken();

        self::assertSame('token-1', $token->token);
        self::assertCount(1, $this->transport->sentRequests());
    }

    public function test_access_token_refetches_once_the_cached_token_has_expired(): void
    {
        $this->transport->queueResponse(new TransportResponse(200, [], '{"access_token":"token-1","token_type":"Bearer","expires_in":3600}'));
        $this->authenticator->accessToken();

        $this->clock->setNow(new DateTimeImmutable('2026-08-31T11:30:00+00:00'));
        $this->transport->queueResponse(new TransportResponse(200, [], '{"access_token":"token-2","token_type":"Bearer","expires_in":3600}'));

        $token = $this->authenticator->accessToken();

        self::assertSame('token-2', $token->token);
        self::assertCount(2, $this->transport->sentRequests());
    }

    public function test_force_refresh_always_fetches_a_new_token(): void
    {
        $this->transport->queueResponse(new TransportResponse(200, [], '{"access_token":"token-1","token_type":"Bearer","expires_in":3600}'));
        $this->authenticator->accessToken();

        $this->transport->queueResponse(new TransportResponse(200, [], '{"access_token":"token-2","token_type":"Bearer","expires_in":3600}'));
        $token = $this->authenticator->forceRefresh();

        self::assertSame('token-2', $token->token);
        self::assertSame($this->tokenCache->get()?->token, 'token-2');
    }

    public function test_a_failed_token_request_throws_an_authentication_exception_without_ever_calling_a_domain_endpoint(): void
    {
        $this->transport->queueResponse(new TransportResponse(400, [], '{"error":"invalid_client","error_description":"Client authentication failed."}'));

        try {
            $this->authenticator->accessToken();
            self::fail('Expected an AuthenticationException.');
        } catch (AuthenticationException $exception) {
            self::assertSame('Client authentication failed.', $exception->getMessage());
            self::assertSame('invalid_client', $exception->errorCode());
        }

        self::assertCount(1, $this->transport->sentRequests());
        self::assertSame('/oauth/token', $this->transport->sentRequests()[0]->path);
    }

    public function test_a_malformed_token_response_throws_an_authentication_exception(): void
    {
        $this->transport->queueResponse(new TransportResponse(200, [], '{"unexpected":true}'));

        $this->expectException(AuthenticationException::class);

        $this->authenticator->accessToken();
    }

    public function test_access_token_stores_the_requested_scopes(): void
    {
        $this->transport->queueResponse(new TransportResponse(200, [], '{"access_token":"token-1","token_type":"Bearer","expires_in":3600}'));

        $token = $this->authenticator->accessToken();

        self::assertSame([Scope::DebtorsWrite, Scope::InvoicesWrite], $token->scopes);
    }
}
