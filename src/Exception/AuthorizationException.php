<?php

declare(strict_types=1);

namespace KBDataSolutions\Sdk\Exception;

use RuntimeException;
use Throwable;

final class AuthorizationException extends RuntimeException implements SdkException
{
    public function __construct(
        string $message,
        private readonly ?string $errorCode = null,
        private readonly ?string $requestId = null,
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }

    public function errorCode(): ?string
    {
        return $this->errorCode;
    }

    public function requestId(): ?string
    {
        return $this->requestId;
    }
}
