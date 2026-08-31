<?php

declare(strict_types=1);

namespace KBDataSolutions\Sdk\Tests\Fakes;

use KBDataSolutions\Sdk\Contract\Sleeper;

final class FakeSleeper implements Sleeper
{
    /**
     * @var list<int>
     */
    private array $sleeps = [];

    public function sleep(int $milliseconds): void
    {
        $this->sleeps[] = $milliseconds;
    }

    /**
     * @return list<int>
     */
    public function sleeps(): array
    {
        return $this->sleeps;
    }
}
