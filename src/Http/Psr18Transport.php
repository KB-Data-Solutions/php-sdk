<?php

declare(strict_types=1);

namespace KBDataSolutions\Sdk\Http;

use KBDataSolutions\Sdk\Exception\TransportException;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;

final class Psr18Transport implements Transport
{
    public function __construct(
        private readonly ClientInterface $httpClient,
        private readonly RequestFactoryInterface $requestFactory,
        private readonly StreamFactoryInterface $streamFactory,
        private readonly string $baseUri,
    ) {
    }

    public function send(TransportRequest $request): TransportResponse
    {
        $psrRequest = $this->requestFactory->createRequest(
            $request->method,
            rtrim($this->baseUri, '/').$request->path,
        );

        foreach ($request->headers as $name => $value) {
            $psrRequest = $psrRequest->withHeader($name, $value);
        }

        if ($request->body !== null) {
            $psrRequest = $psrRequest->withBody($this->streamFactory->createStream($request->body));
        }

        try {
            $psrResponse = $this->httpClient->sendRequest($psrRequest);
        } catch (ClientExceptionInterface $exception) {
            throw new TransportException(
                \sprintf('HTTP request to "%s" failed: %s', $request->path, $exception->getMessage()),
                $exception,
            );
        }

        /** @var array<string, string> $headers */
        $headers = [];

        foreach ($psrResponse->getHeaders() as $name => $values) {
            $headers[(string) $name] = implode(', ', $values);
        }

        return new TransportResponse(
            $psrResponse->getStatusCode(),
            $headers,
            (string) $psrResponse->getBody(),
        );
    }
}
