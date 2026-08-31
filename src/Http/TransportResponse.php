<?php

declare(strict_types=1);

namespace KBDataSolutions\Sdk\Http;

final readonly class TransportResponse
{
    /**
     * @param array<string, string> $headers
     */
    public function __construct(
        public int $statusCode,
        public array $headers,
        public string $body,
    ) {
    }

    public function header(string $name): ?string
    {
        foreach ($this->headers as $headerName => $value) {
            if (strcasecmp($headerName, $name) === 0) {
                return $value;
            }
        }

        return null;
    }

    public function requestId(): ?string
    {
        return $this->header('X-Request-Id');
    }
}
