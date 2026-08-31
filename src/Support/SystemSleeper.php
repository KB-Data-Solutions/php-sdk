<?php

declare(strict_types=1);

namespace KBDataSolutions\Sdk\Support;

use KBDataSolutions\Sdk\Contract\Sleeper;

final class SystemSleeper implements Sleeper
{
    public function sleep(int $milliseconds): void
    {
        usleep($milliseconds * 1000);
    }
}
