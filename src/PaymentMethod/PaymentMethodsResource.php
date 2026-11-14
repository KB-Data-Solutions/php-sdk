<?php

declare(strict_types=1);

namespace KBDataSolutions\Sdk\PaymentMethod;

use KBDataSolutions\Sdk\Client\ApiClient;
use KBDataSolutions\Sdk\Exception\MappingException;
use KBDataSolutions\Sdk\Support\ArrayShape;

final readonly class PaymentMethodsResource
{
    public function __construct(private ApiClient $apiClient)
    {
    }

    /** @param list<PaymentMethod> $methods
     * @return list<PaymentMethod>
     */
    public function sync(array $methods, ?string $idempotencyKey = null): array
    {
        $response = $this->apiClient->postJson(
            '/api/v1/payment-methods',
            ['methods' => array_map(static fn (PaymentMethod $method): array => $method->toArray(), $methods)],
            $idempotencyKey
        );
        $result = [];
        foreach (ArrayShape::requireArray($response, 'methods') as $method) {
            if (!is_array($method)) {
                throw new MappingException('A payment-method response must contain objects.');
            }
            $result[] = PaymentMethod::fromArray($method);
        }
        return $result;
    }
}
