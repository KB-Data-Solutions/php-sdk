<?php

declare(strict_types=1);

namespace KBDataSolutions\Sdk\Debtor;

enum DebtorType: string
{
    case Business = 'business';
    case Consumer = 'consumer';
}
