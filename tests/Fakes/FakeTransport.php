<?php

declare(strict_types=1);

namespace KBDataSolutions\Sdk\Tests\Fakes;

use KBDataSolutions\Sdk\Http\Transport;
use KBDataSolutions\Sdk\Http\TransportRequest;
use KBDataSolutions\Sdk\Http\TransportResponse;
use LogicException;

final class FakeTransport implements Transport
{
    /**
     * @var list<TransportResponse>
     */
    private array $queuedResponses = [];

    /**
     * @var list<TransportRequest>
     */
    private array $sentRequests = [];

    public function queueResponse(TransportResponse $response): void
    {
        $this->queuedResponses[] = $response;
    }

    public function send(TransportRequest $request): TransportResponse
    {
        $this->sentRequests[] = $request;

        $response = array_shift($this->queuedResponses);

        if ($response === null) {
            throw new LogicException('FakeTransport has no queued response left for this request.');
        }

        return $response;
    }

    /**
     * @return list<TransportRequest>
     */
    public function sentRequests(): array
    {
        return $this->sentRequests;
    }

    public function lastRequest(): ?TransportRequest
    {
        $index = array_key_last($this->sentRequests);

        return $index === null ? null : $this->sentRequests[$index];
    }
}
