<?php

declare(strict_types=1);

namespace KBDataSolutions\Sdk\Support;

use DateTimeImmutable;
use KBDataSolutions\Sdk\Contract\Clock;

final class SystemClock implements Clock
{
    public function now(): DateTimeImmutable
    {
        return new DateTimeImmutable();
    }
}
