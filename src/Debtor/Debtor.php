<?php

declare(strict_types=1);

namespace KBDataSolutions\Sdk\Debtor;

use DateTimeImmutable;
use KBDataSolutions\Sdk\Exception\MappingException;
use KBDataSolutions\Sdk\Support\ArrayShape;

final readonly class Debtor
{
    public function __construct(
        public string $id,
        public ?string $externalCustomerId,
        public bool $oneTime,
        public DebtorType $type,
        public DebtorStatus $status,
        public string $rawStatus,
        public ?DebtorSyncErrorCode $errorCode,
        public ?string $errorMessage,
        public DateTimeImmutable $createdAt,
        public DateTimeImmutable $updatedAt,
    ) {
    }

    /**
     * @param array<array-key, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        $rawType = ArrayShape::requireString($data, 'type');
        $type = DebtorType::tryFrom($rawType);

        if ($type === null) {
            throw new MappingException(\sprintf('Field "type" has an unrecognized value "%s".', $rawType));
        }

        $rawStatus = ArrayShape::requireString($data, 'status');
        $rawErrorCode = ArrayShape::optionalString($data, 'error_code');

        return new self(
            ArrayShape::requireString($data, 'id'),
            ArrayShape::optionalString($data, 'external_customer_id'),
            ArrayShape::requireBool($data, 'one_time'),
            $type,
            DebtorStatus::fromApiValue($rawStatus),
            $rawStatus,
            $rawErrorCode === null ? null : DebtorSyncErrorCode::fromApiValue($rawErrorCode),
            ArrayShape::optionalString($data, 'error_message'),
            ArrayShape::requireDateTimeImmutable($data, 'created_at'),
            ArrayShape::requireDateTimeImmutable($data, 'updated_at'),
        );
    }
}
