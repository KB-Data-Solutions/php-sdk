<?php

declare(strict_types=1);

namespace KBDataSolutions\Sdk\Contract;

use DateTimeImmutable;

interface Clock
{
    public function now(): DateTimeImmutable;
}
