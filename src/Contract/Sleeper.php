<?php

declare(strict_types=1);

namespace KBDataSolutions\Sdk\Contract;

interface Sleeper
{
    public function sleep(int $milliseconds): void;
}
