<?php

declare(strict_types=1);

namespace KBDataSolutions\Sdk\Exception;

use RuntimeException;
use Throwable;

final class NotFoundException extends RuntimeException implements SdkException
{
    public function __construct(
        string $message,
        private readonly ?string $requestId = null,
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }

    public function requestId(): ?string
    {
        return $this->requestId;
    }
}
