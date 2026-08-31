# KB Data Solutions PHP SDK

PHP SDK for the KB Data Solutions Commerce Integration API.

## Installation

```bash
composer require kb-data-solutions/php-sdk
```

## Requirements

- PHP 8.3 or higher
- A PSR-18 HTTP client and PSR-17 factories available in your dependency tree (most frameworks already provide these; if none is found, install one, e.g. `composer require guzzlehttp/guzzle`)

## Quick Start

```php
use KBDataSolutions\Sdk\Client;

$client = Client::create(
    clientId: $clientId,
    clientSecret: $clientSecret,
    baseUri: 'https://app.kbdatasolutions.nl',
);

$debtor = $client->debtors()->find($debtorId);
```

## Authentication

The SDK authenticates using OAuth2 Client Credentials. Provide the `clientId` and `clientSecret` issued for your integration; the SDK handles token acquisition and renewal automatically.

## Usage

### Debtors

```php
use KBDataSolutions\Sdk\Debtor\DebtorType;
use KBDataSolutions\Sdk\Debtor\Request\CreateDebtorRequest;

$debtor = $client->debtors()->create(new CreateDebtorRequest(
    externalCustomerId: 'wc-customer-42',
    oneTime: false,
    type: DebtorType::Consumer,
    country: 'NL',
    firstName: 'Jane',
    lastName: 'Doe',
    email: 'jane@example.test',
));

$debtor = $client->debtors()->find($debtor->id);
```

### Invoices

```php
use KBDataSolutions\Sdk\Debtor\DebtorType;
use KBDataSolutions\Sdk\Debtor\Request\CreateDebtorRequest;
use KBDataSolutions\Sdk\Invoice\Currency;
use KBDataSolutions\Sdk\Invoice\DecimalAmount;
use KBDataSolutions\Sdk\Invoice\Request\CreateInvoiceRequest;
use KBDataSolutions\Sdk\Invoice\Request\DocumentUpload;
use KBDataSolutions\Sdk\Invoice\Request\InvoiceLine;
use KBDataSolutions\Sdk\Invoice\TaxTreatment;

$invoice = $client->invoices()->create(new CreateInvoiceRequest(
    externalInvoiceId: 'wc-order-1042',
    invoiceDate: new DateTimeImmutable('2026-08-01'),
    currency: Currency::Eur,
    taxTreatment: TaxTreatment::Domestic,
    destinationCountry: 'NL',
    netAmount: new DecimalAmount('10.00'),
    vatAmount: new DecimalAmount('2.10'),
    grossAmount: new DecimalAmount('12.10'),
    lines: [
        new InvoiceLine(
            externalId: '1',
            description: 'Widget',
            sku: 'WID-1',
            quantity: '1',
            unitPrice: new DecimalAmount('10.00'),
            netAmount: new DecimalAmount('10.00'),
            vatAmount: new DecimalAmount('2.10'),
            grossAmount: new DecimalAmount('12.10'),
            vatRate: '21',
        ),
    ],
    debtor: new CreateDebtorRequest(
        externalCustomerId: 'wc-customer-42',
        oneTime: false,
        type: DebtorType::Consumer,
        country: 'NL',
    ),
    document: new DocumentUpload('invoice.pdf', file_get_contents('/path/to/invoice.pdf')),
));
```

## Error Handling

All SDK exceptions implement `KBDataSolutions\Sdk\Exception\SdkException`, so you can catch every SDK-originated failure with a single type:

```php
use KBDataSolutions\Sdk\Exception\SdkException;
use KBDataSolutions\Sdk\Exception\ValidationException;

try {
    $client->debtors()->create($request);
} catch (ValidationException $exception) {
    // $exception->fieldErrors(): array<string, list<string>>
} catch (SdkException $exception) {
    // any other SDK failure (authentication, authorization, not found, conflict, rate limit, transport, mapping)
}
```

## Idempotency

Write operations accept an optional idempotency key as their second argument; the SDK generates a random one when omitted:

```php
$client->debtors()->create($request, idempotencyKey: 'order-1042-debtor');
```

## Polling

Submissions are processed asynchronously: a create call returns immediately with a `pending` status. Use `waitUntilProcessed()` instead of polling `find()` manually:

```php
use KBDataSolutions\Sdk\Invoice\PollingOptions;

$invoice = $client->invoices()->waitUntilProcessed(
    $invoice->id,
    new PollingOptions(maxAttempts: 10, intervalMilliseconds: 2000),
);
```

It throws `KBDataSolutions\Sdk\Invoice\InvoicePollingTimeoutException` if the invoice has not reached a terminal status after the configured number of attempts; the exception carries the last known `Invoice` and the number of attempts made.

## Pagination

The API currently exposes no list endpoints; there is nothing to paginate.

## Supported PHP Versions

PHP 8.3 and 8.4 are tested in CI.

## Versioning

This package follows [Semantic Versioning](https://semver.org/).

## Contributing

Issues and pull requests are welcome.

## License

Apache-2.0. See [LICENSE](LICENSE).
