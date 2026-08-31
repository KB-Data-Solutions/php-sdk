<?php

declare(strict_types=1);

namespace KBDataSolutions\Sdk\Invoice;

use KBDataSolutions\Sdk\Client\ApiClient;
use KBDataSolutions\Sdk\Contract\Sleeper;
use KBDataSolutions\Sdk\Http\MultipartFile;
use KBDataSolutions\Sdk\Invoice\Request\CreateInvoiceRequest;

final class InvoicesResource
{
    public function __construct(
        private readonly ApiClient $apiClient,
        private readonly Sleeper $sleeper,
    ) {
    }

    public function create(CreateInvoiceRequest $request, ?string $idempotencyKey = null): Invoice
    {
        $document = $request->document;
        $file = $document === null
            ? null
            : new MultipartFile('document', $document->filename, $document->contents, $document->mediaType);

        $payload = json_encode($request->toArray(), \JSON_THROW_ON_ERROR);

        return Invoice::fromArray($this->apiClient->postMultipart(
            '/api/v1/invoices',
            ['payload' => $payload],
            $file,
            $idempotencyKey,
        ));
    }

    public function find(string $invoiceId): Invoice
    {
        return Invoice::fromArray($this->apiClient->get(\sprintf('/api/v1/invoices/%s', rawurlencode($invoiceId))));
    }

    /**
     * Polls find() until the invoice reaches a terminal status, or throws
     * InvoicePollingTimeoutException once $options->maxAttempts is exhausted.
     */
    public function waitUntilProcessed(string $invoiceId, ?PollingOptions $options = null): Invoice
    {
        $options ??= new PollingOptions();
        $interval = $options->intervalMilliseconds;
        $invoice = $this->find($invoiceId);

        for ($attempt = 1; $attempt < $options->maxAttempts; ++$attempt) {
            if ($invoice->status->isTerminal()) {
                return $invoice;
            }

            $this->sleeper->sleep($interval);
            $interval = (int) round($interval * $options->backoffMultiplier);
            $invoice = $this->find($invoiceId);
        }

        if ($invoice->status->isTerminal()) {
            return $invoice;
        }

        throw new InvoicePollingTimeoutException(
            \sprintf('Invoice "%s" did not reach a terminal status after %d attempts.', $invoiceId, $options->maxAttempts),
            $invoice,
            $options->maxAttempts,
        );
    }
}
