<?php

declare(strict_types=1);

namespace KBDataSolutions\Sdk\Debtor\Request;

use InvalidArgumentException;
use KBDataSolutions\Sdk\Debtor\DebtorType;

final readonly class CreateDebtorRequest
{
    public function __construct(
        public ?string $externalCustomerId,
        public bool $oneTime,
        public DebtorType $type,
        public string $country,
        public ?string $companyName = null,
        public ?string $firstName = null,
        public ?string $lastName = null,
        public ?string $email = null,
        public ?string $vatNumber = null,
        public ?string $address = null,
        public ?string $postalCode = null,
        public ?string $city = null,
    ) {
        if (trim($country) === '') {
            throw new InvalidArgumentException('country must not be empty.');
        }

        if (!$oneTime && ($externalCustomerId === null || trim($externalCustomerId) === '')) {
            throw new InvalidArgumentException('externalCustomerId is required unless oneTime is true.');
        }

        if ($type === DebtorType::Business && ($companyName === null || trim($companyName) === '')) {
            throw new InvalidArgumentException('companyName is required when type is DebtorType::Business.');
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'external_customer_id' => $this->externalCustomerId,
            'one_time' => $this->oneTime,
            'type' => $this->type->value,
            'company_name' => $this->companyName,
            'first_name' => $this->firstName,
            'last_name' => $this->lastName,
            'email' => $this->email,
            'vat_number' => $this->vatNumber,
            'country' => $this->country,
            'address' => $this->address,
            'postal_code' => $this->postalCode,
            'city' => $this->city,
        ];
    }
}
