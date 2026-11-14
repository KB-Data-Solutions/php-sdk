<?php

declare(strict_types=1);

namespace KBDataSolutions\Sdk\PaymentMethod;

use InvalidArgumentException;
use KBDataSolutions\Sdk\Support\ArrayShape;

final readonly class PaymentMethod
{
    public function __construct(
        public string $key,
        public string $label,
        public ?string $provider = null,
        public bool $enabled = true,
    ) {
        if ($key === '' || strlen($key) > 191 || $label === '' || strlen($label) > 191) {
            throw new InvalidArgumentException('A payment method requires a key and readable label of at most 191 characters.');
        }
    }

    /** @return array{key: string, label: string, provider: ?string, enabled: bool} */
    public function toArray(): array
    {
        return ['key' => $this->key, 'label' => $this->label, 'provider' => $this->provider, 'enabled' => $this->enabled];
    }

    /** @param array<array-key, mixed> $data */
    public static function fromArray(array $data): self
    {
        return new self(
            ArrayShape::requireString($data, 'key'),
            ArrayShape::requireString($data, 'label'),
            ArrayShape::optionalString($data, 'provider'),
            ArrayShape::requireBool($data, 'enabled')
        );
    }
}
