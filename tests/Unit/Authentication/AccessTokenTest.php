<?php

declare(strict_types=1);

namespace KBDataSolutions\Sdk\Tests\Unit\Authentication;

use DateTimeImmutable;
use KBDataSolutions\Sdk\Authentication\AccessToken;
use PHPUnit\Framework\TestCase;

final class AccessTokenTest extends TestCase
{
    public function test_is_not_expired_well_before_expiry(): void
    {
        $token = new AccessToken('token', new DateTimeImmutable('2026-08-31T11:00:00+00:00'), []);

        self::assertFalse($token->isExpired(new DateTimeImmutable('2026-08-31T10:00:00+00:00')));
    }

    public function test_is_expired_after_actual_expiry(): void
    {
        $token = new AccessToken('token', new DateTimeImmutable('2026-08-31T11:00:00+00:00'), []);

        self::assertTrue($token->isExpired(new DateTimeImmutable('2026-08-31T11:00:01+00:00')));
    }

    public function test_is_expired_within_the_clock_skew_safety_margin(): void
    {
        $token = new AccessToken('token', new DateTimeImmutable('2026-08-31T11:00:00+00:00'), []);

        self::assertTrue($token->isExpired(new DateTimeImmutable('2026-08-31T10:59:35+00:00')));
    }
}
