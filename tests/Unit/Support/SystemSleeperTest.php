<?php

declare(strict_types=1);

namespace KBDataSolutions\Sdk\Tests\Unit\Support;

use KBDataSolutions\Sdk\Support\SystemSleeper;
use PHPUnit\Framework\TestCase;

final class SystemSleeperTest extends TestCase
{
    public function test_sleep_blocks_for_at_least_the_requested_duration(): void
    {
        $start = microtime(true);
        (new SystemSleeper())->sleep(10);
        $elapsedMilliseconds = (microtime(true) - $start) * 1000;

        self::assertGreaterThanOrEqual(10, $elapsedMilliseconds);
    }
}
