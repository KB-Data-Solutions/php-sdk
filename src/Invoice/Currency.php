<?php

declare(strict_types=1);

namespace KBDataSolutions\Sdk\Invoice;

/**
 * The backend's own domain model reserves USD and GBP, but request validation currently
 * accepts only EUR. Modeling the single accepted case as an enum documents this precisely;
 * adding a case later is additive, not a breaking change.
 */
enum Currency: string
{
    case Eur = 'EUR';
}
