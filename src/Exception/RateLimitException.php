<?php

declare(strict_types=1);

namespace KBDataSolutions\Sdk\Exception;

use RuntimeException;
use Throwable;

final class RateLimitException extends RuntimeException implements SdkException
{
    public function __construct(
        string $message,
        private readonly ?int $retryAfterSeconds = null,
        private readonly ?string $requestId = null,
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }

    public function retryAfterSeconds(): ?int
    {
        return $this->retryAfterSeconds;
    }

    public function requestId(): ?string
    {
        return $this->requestId;
    }
}
