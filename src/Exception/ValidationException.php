<?php

declare(strict_types=1);

namespace KBDataSolutions\Sdk\Exception;

use RuntimeException;
use Throwable;

final class ValidationException extends RuntimeException implements SdkException
{
    /**
     * @param array<string, list<string>> $fieldErrors
     */
    public function __construct(
        string $message,
        private readonly array $fieldErrors = [],
        private readonly ?string $errorCode = null,
        private readonly ?string $requestId = null,
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }

    /**
     * @return array<string, list<string>>
     */
    public function fieldErrors(): array
    {
        return $this->fieldErrors;
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
