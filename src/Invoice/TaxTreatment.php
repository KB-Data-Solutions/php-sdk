<?php

declare(strict_types=1);

namespace KBDataSolutions\Sdk\Invoice;

/**
 * Mirrors the backend's App\Enums\Commerce\TaxTreatment. "domestic" (seller's own
 * country), "icp" (EU B2B reverse charge), "oss" (EU B2C distance selling), and
 * "export" (non-EU, zero-rated) are all accepted by the Commerce API.
 */
enum TaxTreatment: string
{
    case Domestic = 'domestic';
    case Icp = 'icp';
    case Oss = 'oss';
    case Export = 'export';
}
