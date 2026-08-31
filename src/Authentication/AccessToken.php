<?php

declare(strict_types=1);

namespace KBDataSolutions\Sdk\Authentication;

use DateInterval;
use DateTimeImmutable;

final readonly class AccessToken
{
    /**
     * Treat the token as expired this long before its actual expiry, to absorb clock skew
     * and the time an in-flight request takes to reach the API.
     */
    private const EXPIRY_SAFETY_MARGIN_SECONDS = 30;

    /**
     * @param list<Scope> $scopes
     */
    public function __construct(
        public string $token,
        public DateTimeImmutable $expiresAt,
        public array $scopes,
    ) {
    }

    public function isExpired(DateTimeImmutable $now): bool
    {
        return $now >= $this->expiresAt->sub(new DateInterval(\sprintf('PT%dS', self::EXPIRY_SAFETY_MARGIN_SECONDS)));
    }
}
