<?php

declare(strict_types=1);

namespace KBDataSolutions\Sdk\Tests\Unit\Debtor;

use DateTimeImmutable;
use KBDataSolutions\Sdk\Authentication\AccessToken;
use KBDataSolutions\Sdk\Client\ApiClient;
use KBDataSolutions\Sdk\Debtor\DebtorsResource;
use KBDataSolutions\Sdk\Debtor\DebtorType;
use KBDataSolutions\Sdk\Debtor\Request\CreateDebtorRequest;
use KBDataSolutions\Sdk\Exception\AuthenticationException;
use KBDataSolutions\Sdk\Exception\AuthorizationException;
use KBDataSolutions\Sdk\Exception\ConflictException;
use KBDataSolutions\Sdk\Exception\NotFoundException;
use KBDataSolutions\Sdk\Exception\ValidationException;
use KBDataSolutions\Sdk\Http\TransportResponse;
use KBDataSolutions\Sdk\Tests\Fakes\FakeAuthenticator;
use KBDataSolutions\Sdk\Tests\Fakes\FakeTransport;
use PHPUnit\Framework\TestCase;

final class DebtorsResourceTest extends TestCase
{
    private FakeTransport $transport;

    private DebtorsResource $debtors;

    protected function setUp(): void
    {
        $this->transport = new FakeTransport();
        $authenticator = new FakeAuthenticator(new AccessToken('token', new DateTimeImmutable('+1 hour'), []));
        $this->debtors = new DebtorsResource(new ApiClient($this->transport, $authenticator));
    }

    public function test_create_submits_the_request_and_maps_the_response(): void
    {
        $this->transport->queueResponse(new TransportResponse(202, [], '{"data":{
            "id":"1","external_customer_id":"cust-1","one_time":false,"type":"consumer","status":"pending",
            "error_code":null,"error_message":null,"created_at":"2026-08-31T10:00:00+00:00","updated_at":"2026-08-31T10:00:00+00:00"
        }}'));

        $debtor = $this->debtors->create(new CreateDebtorRequest(
            externalCustomerId: 'cust-1',
            oneTime: false,
            type: DebtorType::Consumer,
            country: 'NL',
        ));

        self::assertSame('1', $debtor->id);

        $request = $this->transport->lastRequest();
        self::assertNotNull($request);
        self::assertSame('POST', $request->method);
        self::assertSame('/api/v1/debtors', $request->path);
    }

    public function test_find_requests_the_correct_path_and_maps_the_response(): void
    {
        $this->transport->queueResponse(new TransportResponse(200, [], '{"data":{
            "id":"1","external_customer_id":"cust-1","one_time":false,"type":"consumer","status":"synchronized",
            "error_code":null,"error_message":null,"created_at":"2026-08-31T10:00:00+00:00","updated_at":"2026-08-31T10:00:00+00:00"
        }}'));

        $debtor = $this->debtors->find('1');

        self::assertSame('1', $debtor->id);

        $request = $this->transport->lastRequest();
        self::assertNotNull($request);
        self::assertSame('GET', $request->method);
        self::assertSame('/api/v1/debtors/1', $request->path);
    }

    public function test_find_throws_not_found_exception_on_404(): void
    {
        $this->transport->queueResponse(new TransportResponse(404, [], '{"error":{"code":"NOT_FOUND","message":"Debtor not found."}}'));

        $this->expectException(NotFoundException::class);

        $this->debtors->find('unknown');
    }

    public function test_create_throws_validation_exception_on_422(): void
    {
        $this->transport->queueResponse(new TransportResponse(422, [], '{"error":{"code":"VALIDATION_FAILED","message":"Invalid.","details":{"fields":{"type":["The type field is required."]}}}}'));

        $this->expectException(ValidationException::class);

        $this->debtors->create(new CreateDebtorRequest(
            externalCustomerId: 'cust-1',
            oneTime: false,
            type: DebtorType::Consumer,
            country: 'NL',
        ));
    }

    public function test_create_throws_authorization_exception_on_403(): void
    {
        $this->transport->queueResponse(new TransportResponse(403, [], '{"error":{"code":"SUBSCRIPTION_INACTIVE","message":"Forbidden."}}'));

        $this->expectException(AuthorizationException::class);

        $this->debtors->create(new CreateDebtorRequest(
            externalCustomerId: 'cust-1',
            oneTime: false,
            type: DebtorType::Consumer,
            country: 'NL',
        ));
    }

    public function test_create_throws_conflict_exception_on_409(): void
    {
        $this->transport->queueResponse(new TransportResponse(409, [], '{"error":{"code":"IDEMPOTENCY_KEY_REUSED","message":"Conflict."}}'));

        $this->expectException(ConflictException::class);

        $this->debtors->create(new CreateDebtorRequest(
            externalCustomerId: 'cust-1',
            oneTime: false,
            type: DebtorType::Consumer,
            country: 'NL',
        ));
    }

    public function test_find_throws_authentication_exception_when_reauthentication_also_fails(): void
    {
        $this->transport->queueResponse(new TransportResponse(401, [], '{"error":{"code":"UNAUTHENTICATED","message":"Expired."}}'));
        $this->transport->queueResponse(new TransportResponse(401, [], '{"error":{"code":"UNAUTHENTICATED","message":"Still expired."}}'));

        $this->expectException(AuthenticationException::class);

        $this->debtors->find('1');
    }
}
