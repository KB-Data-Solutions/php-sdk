<?php

declare(strict_types=1);

namespace KBDataSolutions\Sdk\Tests\Unit\Debtor;

use KBDataSolutions\Sdk\Debtor\Debtor;
use KBDataSolutions\Sdk\Debtor\DebtorStatus;
use KBDataSolutions\Sdk\Debtor\DebtorSyncErrorCode;
use KBDataSolutions\Sdk\Debtor\DebtorType;
use KBDataSolutions\Sdk\Exception\MappingException;
use PHPUnit\Framework\TestCase;

final class DebtorTest extends TestCase
{
    /**
     * @return array<string, mixed>
     */
    private static function validPayload(): array
    {
        return [
            'id' => '123',
            'external_customer_id' => 'cust-1',
            'one_time' => false,
            'type' => 'consumer',
            'status' => 'pending',
            'error_code' => null,
            'error_message' => null,
            'created_at' => '2026-08-31T10:00:00+00:00',
            'updated_at' => '2026-08-31T10:00:00+00:00',
        ];
    }

    public function test_from_array_maps_a_valid_payload(): void
    {
        $debtor = Debtor::fromArray(self::validPayload());

        self::assertSame('123', $debtor->id);
        self::assertSame('cust-1', $debtor->externalCustomerId);
        self::assertFalse($debtor->oneTime);
        self::assertSame(DebtorType::Consumer, $debtor->type);
        self::assertSame(DebtorStatus::Pending, $debtor->status);
        self::assertSame('pending', $debtor->rawStatus);
        self::assertNull($debtor->errorCode);
        self::assertNull($debtor->errorMessage);
        self::assertSame('2026-08-31T10:00:00+00:00', $debtor->createdAt->format(DATE_ATOM));
    }

    public function test_from_array_maps_a_failed_debtor_with_an_error_code(): void
    {
        $payload = array_merge(self::validPayload(), [
            'status' => 'failed',
            'error_code' => 'ACCOUNTING_SYNC_FAILED',
            'error_message' => 'The accounting system rejected the debtor.',
        ]);

        $debtor = Debtor::fromArray($payload);

        self::assertSame(DebtorStatus::Failed, $debtor->status);
        self::assertSame(DebtorSyncErrorCode::AccountingSyncFailed, $debtor->errorCode);
        self::assertSame('The accounting system rejected the debtor.', $debtor->errorMessage);
    }

    public function test_from_array_falls_back_to_unknown_for_an_unrecognized_status(): void
    {
        $payload = array_merge(self::validPayload(), ['status' => 'a_future_status']);

        $debtor = Debtor::fromArray($payload);

        self::assertSame(DebtorStatus::Unknown, $debtor->status);
        self::assertSame('a_future_status', $debtor->rawStatus);
    }

    public function test_from_array_falls_back_to_unknown_for_an_unrecognized_error_code(): void
    {
        $payload = array_merge(self::validPayload(), ['error_code' => 'A_FUTURE_ERROR_CODE']);

        $debtor = Debtor::fromArray($payload);

        self::assertSame(DebtorSyncErrorCode::Unknown, $debtor->errorCode);
    }

    public function test_from_array_throws_a_mapping_exception_for_an_unrecognized_type(): void
    {
        $payload = array_merge(self::validPayload(), ['type' => 'organization']);

        $this->expectException(MappingException::class);

        Debtor::fromArray($payload);
    }

    public function test_from_array_throws_a_mapping_exception_when_a_required_field_is_missing(): void
    {
        $payload = self::validPayload();
        unset($payload['id']);

        $this->expectException(MappingException::class);

        Debtor::fromArray($payload);
    }
}
