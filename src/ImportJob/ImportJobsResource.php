<?php

declare(strict_types=1);

namespace KBDataSolutions\Sdk\ImportJob;

use KBDataSolutions\Sdk\Client\ApiClient;
use KBDataSolutions\Sdk\Exception\MappingException;
use KBDataSolutions\Sdk\ImportJob\Request\ImportJobCompletion;
use KBDataSolutions\Sdk\ImportJob\Request\ImportJobProgress;
use KBDataSolutions\Sdk\Support\ArrayShape;

final readonly class ImportJobsResource
{
    public function __construct(private ApiClient $apiClient)
    {
    }

    /** @return list<ImportJob> */
    public function list(): array
    {
        $response = $this->apiClient->get('/api/v1/import-jobs');
        $jobs = [];
        foreach (ArrayShape::requireArray($response, 'jobs') as $job) {
            if (!is_array($job)) {
                throw new MappingException('An import-jobs response must contain objects.');
            }
            $jobs[] = ImportJob::fromArray($job);
        }

        return $jobs;
    }

    public function claim(string $jobId, ?string $idempotencyKey = null): ImportJob
    {
        return ImportJob::fromArray($this->apiClient->postJson($this->path($jobId, 'claim'), [], $idempotencyKey));
    }

    public function reportProgress(string $jobId, ImportJobProgress $progress, ?string $idempotencyKey = null): ImportJob
    {
        return ImportJob::fromArray($this->apiClient->postJson($this->path($jobId, 'progress'), $progress->toArray(), $idempotencyKey));
    }

    public function complete(string $jobId, ImportJobCompletion $completion, ?string $idempotencyKey = null): ImportJob
    {
        return ImportJob::fromArray($this->apiClient->postJson($this->path($jobId, 'complete'), $completion->toArray(), $idempotencyKey));
    }

    private function path(string $jobId, string $action): string
    {
        return '/api/v1/import-jobs/'.rawurlencode($jobId).'/'.$action;
    }
}
