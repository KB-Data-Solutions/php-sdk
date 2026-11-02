<?php

declare(strict_types=1);

namespace KBDataSolutions\Sdk\Authentication;

enum Scope: string
{
    case DebtorsWrite = 'debtors:write';
    case InvoicesWrite = 'invoices:write';
    case DebtorsRead = 'debtors:read';
    case InvoicesRead = 'invoices:read';
}
