<?php

declare(strict_types=1);

namespace KBDataSolutions\Sdk\Tests\Unit\ImportJob;

use DateTimeImmutable;
use InvalidArgumentException;
use KBDataSolutions\Sdk\Authentication\AccessToken;
use KBDataSolutions\Sdk\Client;
use KBDataSolutions\Sdk\Exception\ConflictException;
use KBDataSolutions\Sdk\Exception\MappingException;
use KBDataSolutions\Sdk\Http\TransportResponse;
use KBDataSolutions\Sdk\ImportJob\Request\ImportJobCompletion;
use KBDataSolutions\Sdk\ImportJob\Request\ImportJobProgress;
use KBDataSolutions\Sdk\Tests\Fakes\FakeAuthenticator;
use KBDataSolutions\Sdk\Tests\Fakes\FakeTransport;
use PHPUnit\Framework\TestCase;

final class ImportJobsResourceTest extends TestCase
{
    private const string JOB = '{"id":"12","status":"%s","from_date":"2026-10-01","to_date":"2026-11-18T10:00:00+00:00","cursor":%s,"orders_sent":%d,"orders_failed":0,"refunds_sent":0}';

    private FakeTransport $transport;

    private Client $client;

    protected function setUp(): void
    {
        $this->transport = new FakeTransport();
        $this->client = Client::create(
            'id',
            'secret',
            'https://api.example.test',
            'woocommerce',
            transport: $this->transport,
            authenticator: new FakeAuthenticator(new AccessToken('token', new DateTimeImmutable('+1 hour'), [])),
        );
    }

    public function test_list_maps_open_jobs(): void
    {
        $this->transport->queueResponse(new TransportResponse(200, [], '{"data":{"jobs":['.sprintf(self::JOB, 'pending', 'null', 0).']}}'));

        $jobs = $this->client->importJobs()->list();

        self::assertCount(1, $jobs);
        self::assertSame('12', $jobs[0]->id);
        self::assertTrue($jobs[0]->isPending());
        self::assertSame('2026-10-01', $jobs[0]->fromDate);
        self::assertNull($jobs[0]->cursor);
        $request = $this->transport->lastRequest();
        self::assertNotNull($request);
        self::assertSame('GET', $request->method);
        self::assertSame('/api/v1/import-jobs', $request->path);
        self::assertSame('woocommerce', $request->headers['X-Commerce-Platform']);
    }

    public function test_claim_progress_and_complete_post_to_the_job(): void
    {
        $this->transport->queueResponse(new TransportResponse(200, [], '{"data":'.sprintf(self::JOB, 'processing', 'null', 0).'}'));
        $job = $this->client->importJobs()->claim('12', 'claim-12');
        self::assertSame('processing', $job->status);
        $request = $this->transport->lastRequest();
        self::assertNotNull($request);
        self::assertSame('/api/v1/import-jobs/12/claim', $request->path);
        self::assertSame('claim-12', $request->headers['Idempotency-Key']);

        $this->transport->queueResponse(new TransportResponse(200, [], '{"data":'.sprintf(self::JOB, 'processing', '"501"', 50).'}'));
        $job = $this->client->importJobs()->reportProgress('12', new ImportJobProgress('501', 50, 0, 3));
        self::assertSame('501', $job->cursor);
        $request = $this->transport->lastRequest();
        self::assertNotNull($request);
        self::assertSame('/api/v1/import-jobs/12/progress', $request->path);
        $body = json_decode((string) $request->body, true, 512, JSON_THROW_ON_ERROR);
        self::assertSame(['cursor' => '501', 'orders_sent' => 50, 'orders_failed' => 0, 'refunds_sent' => 3, 'last_error' => null], $body);

        $this->transport->queueResponse(new TransportResponse(200, [], '{"data":'.sprintf(self::JOB, 'completed', '"501"', 50).'}'));
        $job = $this->client->importJobs()->complete('12', ImportJobCompletion::fromTotals(50, 0, 3));
        self::assertSame('completed', $job->status);
        $request = $this->transport->lastRequest();
        self::assertNotNull($request);
        self::assertSame('/api/v1/import-jobs/12/complete', $request->path);
    }

    public function test_claiming_a_taken_job_throws_conflict(): void
    {
        $this->transport->queueResponse(new TransportResponse(409, [], '{"error":{"code":"IMPORT_NOT_CLAIMABLE","message":"already claimed"}}'));

        $this->expectException(ConflictException::class);
        $this->client->importJobs()->claim('12');
    }

    public function test_malformed_job_list_is_rejected(): void
    {
        $this->transport->queueResponse(new TransportResponse(200, [], '{"data":{"jobs":["nope"]}}'));

        $this->expectException(MappingException::class);
        $this->client->importJobs()->list();
    }

    public function test_completion_status_is_derived_from_totals(): void
    {
        self::assertSame(ImportJobCompletion::COMPLETED, ImportJobCompletion::fromTotals(10, 0, 0)->status);
        self::assertSame(ImportJobCompletion::COMPLETED_WITH_ERRORS, ImportJobCompletion::fromTotals(9, 1, 0)->status);
        self::assertSame(ImportJobCompletion::FAILED, ImportJobCompletion::fromTotals(0, 3, 0)->status);
        self::assertSame(ImportJobCompletion::COMPLETED, ImportJobCompletion::fromTotals(0, 0, 0)->status);
    }

    public function test_progress_rejects_negative_totals(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new ImportJobProgress(null, -1, 0, 0);
    }
}
