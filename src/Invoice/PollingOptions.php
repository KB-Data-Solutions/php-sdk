<?php

declare(strict_types=1);

namespace KBDataSolutions\Sdk\Invoice;

use InvalidArgumentException;

final readonly class PollingOptions
{
    public function __construct(
        public int $maxAttempts = 10,
        public int $intervalMilliseconds = 2000,
        public float $backoffMultiplier = 1.0,
    ) {
        if ($maxAttempts < 1) {
            throw new InvalidArgumentException('maxAttempts must be at least 1.');
        }

        if ($intervalMilliseconds < 0) {
            throw new InvalidArgumentException('intervalMilliseconds must not be negative.');
        }

        if ($backoffMultiplier < 1.0) {
            throw new InvalidArgumentException('backoffMultiplier must be at least 1.0.');
        }
    }
}
