<?php

declare(strict_types=1);

namespace KBDataSolutions\Sdk\Payment;

use KBDataSolutions\Sdk\Client\ApiClient;
use KBDataSolutions\Sdk\Payment\Request\CreatePaymentRequest;

final readonly class PaymentsResource
{
    public function __construct(private ApiClient $apiClient)
    {
    }

    public function create(CreatePaymentRequest $request, ?string $idempotencyKey = null): Payment
    {
        return Payment::fromArray($this->apiClient->postJson('/api/v1/payments', $request->toArray(), $idempotencyKey));
    }

    public function find(string $paymentId): Payment
    {
        return Payment::fromArray($this->apiClient->get('/api/v1/payments/'.rawurlencode($paymentId)));
    }
}
