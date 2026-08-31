<?php

declare(strict_types=1);

namespace KBDataSolutions\Sdk\Tests\Unit\Support;

use DateTimeImmutable;
use KBDataSolutions\Sdk\Support\SystemClock;
use PHPUnit\Framework\TestCase;

final class SystemClockTest extends TestCase
{
    public function test_now_returns_current_time(): void
    {
        $before = new DateTimeImmutable();
        $now = (new SystemClock())->now();
        $after = new DateTimeImmutable();

        self::assertGreaterThanOrEqual($before->getTimestamp(), $now->getTimestamp());
        self::assertLessThanOrEqual($after->getTimestamp(), $now->getTimestamp());
    }
}
