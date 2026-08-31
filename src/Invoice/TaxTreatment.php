<?php

declare(strict_types=1);

namespace KBDataSolutions\Sdk\Invoice;

/**
 * The backend's own domain model reserves "icp" and "oss", but request validation
 * currently accepts only "domestic". Modeling the single accepted case as an enum
 * documents this precisely; adding a case later is additive, not a breaking change.
 */
enum TaxTreatment: string
{
    case Domestic = 'domestic';
}
