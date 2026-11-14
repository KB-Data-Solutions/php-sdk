<?php

declare(strict_types=1);

namespace KBDataSolutions\Sdk\Payment\Request;

use DateTimeImmutable;
use InvalidArgumentException;
use KBDataSolutions\Sdk\Invoice\Currency;
use KBDataSolutions\Sdk\Invoice\DecimalAmount;

final readonly class CreatePaymentRequest
{
    public function __construct(
        public string $externalPaymentId,
        public string $externalInvoiceId,
        public string $paymentMethod,
        public string $transactionReference,
        public DecimalAmount $amount,
        public DateTimeImmutable $paidAt,
        public Currency $currency = Currency::Eur,
    ) {
        if (preg_match('/^\d+(\.\d{1,2})?$/', $amount->value) !== 1 || str_replace(['0', '.'], '', $amount->value) === '') {
            throw new InvalidArgumentException('A confirmed payment requires a positive amount.');
        }
        foreach ([$externalPaymentId, $externalInvoiceId, $paymentMethod, $transactionReference] as $reference) {
            if ($reference === '' || strlen($reference) > 191) {
                throw new InvalidArgumentException('Payment references must contain 1 to 191 characters.');
            }
        }
    }

    /** @return array<string, string> */
    public function toArray(): array
    {
        [$integer, $fraction] = array_pad(explode('.', $this->amount->value, 2), 2, '');
        return [
            'external_payment_id' => $this->externalPaymentId, 'external_invoice_id' => $this->externalInvoiceId,
            'payment_method' => $this->paymentMethod, 'transaction_reference' => $this->transactionReference,
            'status' => 'confirmed', 'currency' => $this->currency->value,
            'amount' => $integer.'.'.str_pad($fraction, 2, '0'), 'paid_at' => $this->paidAt->format(DATE_ATOM),
        ];
    }
}
