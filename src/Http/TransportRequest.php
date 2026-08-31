<?php

declare(strict_types=1);

namespace KBDataSolutions\Sdk\Http;

final readonly class TransportRequest
{
    /**
     * @param array<string, string> $headers
     */
    public function __construct(
        public string $method,
        public string $path,
        public array $headers = [],
        public ?string $body = null,
    ) {
    }
}
