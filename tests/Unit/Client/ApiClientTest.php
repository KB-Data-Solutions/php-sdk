<?php

declare(strict_types=1);

namespace KBDataSolutions\Sdk\Tests\Unit\Client;

use DateTimeImmutable;
use KBDataSolutions\Sdk\Authentication\AccessToken;
use KBDataSolutions\Sdk\Client\ApiClient;
use KBDataSolutions\Sdk\Exception\AuthenticationException;
use KBDataSolutions\Sdk\Exception\MappingException;
use KBDataSolutions\Sdk\Exception\NotFoundException;
use KBDataSolutions\Sdk\Http\MultipartFile;
use KBDataSolutions\Sdk\Http\TransportResponse;
use KBDataSolutions\Sdk\Tests\Fakes\FakeAuthenticator;
use KBDataSolutions\Sdk\Tests\Fakes\FakeTransport;
use PHPUnit\Framework\TestCase;

final class ApiClientTest extends TestCase
{
    private FakeTransport $transport;

    private FakeAuthenticator $authenticator;

    private ApiClient $apiClient;

    protected function setUp(): void
    {
        $this->transport = new FakeTransport();
        $this->authenticator = new FakeAuthenticator(new AccessToken('token-1', new DateTimeImmutable('+1 hour'), []));
        $this->apiClient = new ApiClient($this->transport, $this->authenticator);
    }

    public function test_get_unwraps_the_data_envelope(): void
    {
        $this->transport->queueResponse(new TransportResponse(200, [], '{"data":{"id":"1","status":"pending"}}'));

        $result = $this->apiClient->get('/api/v1/debtors/1');

        self::assertSame(['id' => '1', 'status' => 'pending'], $result);

        $request = $this->transport->lastRequest();
        self::assertNotNull($request);
        self::assertSame('Bearer token-1', $request->headers['Authorization']);
    }

    public function test_post_json_generates_an_idempotency_key_when_none_given(): void
    {
        $this->transport->queueResponse(new TransportResponse(202, [], '{"data":{"id":"1"}}'));

        $this->apiClient->postJson('/api/v1/debtors', ['type' => 'consumer']);

        $request = $this->transport->lastRequest();
        self::assertNotNull($request);

        self::assertArrayHasKey('Idempotency-Key', $request->headers);
        self::assertNotSame('', $request->headers['Idempotency-Key']);
        self::assertSame('application/json', $request->headers['Content-Type']);
        self::assertSame('{"type":"consumer"}', $request->body);
    }

    public function test_post_json_uses_the_caller_supplied_idempotency_key_verbatim(): void
    {
        $this->transport->queueResponse(new TransportResponse(202, [], '{"data":{"id":"1"}}'));

        $this->apiClient->postJson('/api/v1/debtors', ['type' => 'consumer'], 'my-idempotency-key');

        $request = $this->transport->lastRequest();
        self::assertNotNull($request);
        self::assertSame('my-idempotency-key', $request->headers['Idempotency-Key']);
    }

    public function test_post_multipart_builds_a_multipart_body_with_the_idempotency_header(): void
    {
        $this->transport->queueResponse(new TransportResponse(202, [], '{"data":{"id":"1"}}'));

        $file = new MultipartFile('document', 'invoice.pdf', '%PDF fake', 'application/pdf');
        $this->apiClient->postMultipart('/api/v1/invoices', ['payload' => '{"a":1}'], $file, 'my-key');

        $request = $this->transport->lastRequest();

        self::assertNotNull($request);
        self::assertStringStartsWith('multipart/form-data; boundary=', $request->headers['Content-Type']);
        self::assertStringContainsString('name="payload"', (string) $request->body);
        self::assertStringContainsString('name="document"; filename="invoice.pdf"', (string) $request->body);
        self::assertSame('my-key', $request->headers['Idempotency-Key']);
    }

    public function test_a_single_401_triggers_exactly_one_transparent_refresh_and_retry(): void
    {
        $this->transport->queueResponse(new TransportResponse(401, [], '{"error":{"code":"UNAUTHENTICATED","message":"Expired."}}'));
        $this->transport->queueResponse(new TransportResponse(200, [], '{"data":{"id":"1"}}'));

        $result = $this->apiClient->get('/api/v1/debtors/1');

        self::assertSame(['id' => '1'], $result);
        self::assertSame(1, $this->authenticator->forceRefreshCalls());
        self::assertCount(2, $this->transport->sentRequests());
    }

    public function test_a_second_401_after_refresh_propagates_as_authentication_exception(): void
    {
        $this->transport->queueResponse(new TransportResponse(401, [], '{"error":{"code":"UNAUTHENTICATED","message":"Expired."}}'));
        $this->transport->queueResponse(new TransportResponse(401, [], '{"error":{"code":"UNAUTHENTICATED","message":"Still expired."}}'));

        try {
            $this->apiClient->get('/api/v1/debtors/1');
            self::fail('Expected an AuthenticationException to be thrown.');
        } catch (AuthenticationException $exception) {
            self::assertSame('Still expired.', $exception->getMessage());
        }

        self::assertSame(1, $this->authenticator->forceRefreshCalls());
        self::assertCount(2, $this->transport->sentRequests());
    }

    public function test_error_responses_are_mapped_via_the_error_mapper(): void
    {
        $this->transport->queueResponse(new TransportResponse(404, [], '{"error":{"code":"NOT_FOUND","message":"Debtor not found."}}'));

        $this->expectException(NotFoundException::class);
        $this->expectExceptionMessage('Debtor not found.');

        $this->apiClient->get('/api/v1/debtors/unknown');
    }

    public function test_a_non_json_response_body_throws_a_mapping_exception(): void
    {
        $this->transport->queueResponse(new TransportResponse(200, [], 'not json'));

        $this->expectException(MappingException::class);

        $this->apiClient->get('/api/v1/debtors/1');
    }

    public function test_a_response_missing_the_data_envelope_throws_a_mapping_exception(): void
    {
        $this->transport->queueResponse(new TransportResponse(200, [], '{"unexpected":true}'));

        $this->expectException(MappingException::class);

        $this->apiClient->get('/api/v1/debtors/1');
    }
}
