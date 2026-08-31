<?php

declare(strict_types=1);

namespace KBDataSolutions\Sdk\Tests\Fakes;

use DateTimeImmutable;
use KBDataSolutions\Sdk\Contract\Clock;

final class FakeClock implements Clock
{
    public function __construct(private DateTimeImmutable $now)
    {
    }

    public function now(): DateTimeImmutable
    {
        return $this->now;
    }

    public function setNow(DateTimeImmutable $now): void
    {
        $this->now = $now;
    }
}
