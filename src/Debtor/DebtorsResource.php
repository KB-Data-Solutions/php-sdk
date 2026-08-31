<?php

declare(strict_types=1);

namespace KBDataSolutions\Sdk\Debtor;

use KBDataSolutions\Sdk\Client\ApiClient;
use KBDataSolutions\Sdk\Debtor\Request\CreateDebtorRequest;

final class DebtorsResource
{
    public function __construct(private readonly ApiClient $apiClient)
    {
    }

    public function create(CreateDebtorRequest $request, ?string $idempotencyKey = null): Debtor
    {
        return Debtor::fromArray($this->apiClient->postJson('/api/v1/debtors', $request->toArray(), $idempotencyKey));
    }

    public function find(string $debtorId): Debtor
    {
        return Debtor::fromArray($this->apiClient->get(\sprintf('/api/v1/debtors/%s', rawurlencode($debtorId))));
    }
}
