<?php

declare(strict_types=1);

namespace KBDataSolutions\Sdk\Client;

use KBDataSolutions\Sdk\Authentication\AccessToken;
use KBDataSolutions\Sdk\Authentication\Authenticator;
use KBDataSolutions\Sdk\Exception\MappingException;
use KBDataSolutions\Sdk\Http\IdempotencyKeyGenerator;
use KBDataSolutions\Sdk\Http\MultipartFile;
use KBDataSolutions\Sdk\Http\MultipartStreamBuilder;
use KBDataSolutions\Sdk\Http\Transport;
use KBDataSolutions\Sdk\Http\TransportRequest;
use KBDataSolutions\Sdk\Http\TransportResponse;
use KBDataSolutions\Sdk\Support\ArrayShape;

/**
 * @internal Not part of the SDK's public API; use the resource classes returned by Client instead.
 */
final class ApiClient
{
    public function __construct(
        private readonly Transport $transport,
        private readonly Authenticator $authenticator,
    ) {
    }

    /**
     * @return array<array-key, mixed>
     */
    public function get(string $path): array
    {
        return $this->unwrap($this->dispatch(new TransportRequest('GET', $path, ['Accept' => 'application/json'])));
    }

    /**
     * @param array<string, mixed> $json
     * @return array<array-key, mixed>
     */
    public function postJson(string $path, array $json, ?string $idempotencyKey = null): array
    {
        $headers = [
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
            'Idempotency-Key' => $idempotencyKey ?? IdempotencyKeyGenerator::generate(),
        ];

        $body = json_encode($json, \JSON_THROW_ON_ERROR);

        return $this->unwrap($this->dispatch(new TransportRequest('POST', $path, $headers, $body)));
    }

    /**
     * @param array<string, string> $fields
     * @return array<array-key, mixed>
     */
    public function postMultipart(string $path, array $fields, ?MultipartFile $file, ?string $idempotencyKey = null): array
    {
        $built = (new MultipartStreamBuilder())->build($fields, $file);

        $headers = [
            'Accept' => 'application/json',
            'Content-Type' => $built['contentType'],
            'Idempotency-Key' => $idempotencyKey ?? IdempotencyKeyGenerator::generate(),
        ];

        return $this->unwrap($this->dispatch(new TransportRequest('POST', $path, $headers, $built['body'])));
    }

    private function dispatch(TransportRequest $request): TransportResponse
    {
        $response = $this->transport->send($this->authorize($request, $this->authenticator->accessToken()));

        // A 401 on a domain endpoint means the request never reached business processing,
        // so retrying with a freshly issued token cannot duplicate any side effect.
        if ($response->statusCode === 401) {
            $response = $this->transport->send($this->authorize($request, $this->authenticator->forceRefresh()));
        }

        if ($response->statusCode >= 400) {
            throw ErrorMapper::mapToException($response);
        }

        return $response;
    }

    private function authorize(TransportRequest $request, AccessToken $token): TransportRequest
    {
        return new TransportRequest(
            $request->method,
            $request->path,
            [...$request->headers, 'Authorization' => 'Bearer '.$token->token],
            $request->body,
        );
    }

    /**
     * @return array<array-key, mixed>
     */
    private function unwrap(TransportResponse $response): array
    {
        $decoded = json_decode($response->body, true);

        if (!\is_array($decoded)) {
            throw new MappingException('Response body is not a valid JSON object.');
        }

        return ArrayShape::requireArray($decoded, 'data');
    }
}
