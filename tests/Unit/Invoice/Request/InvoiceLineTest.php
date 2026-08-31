<?php

declare(strict_types=1);

namespace KBDataSolutions\Sdk\Tests\Unit\Invoice\Request;

use InvalidArgumentException;
use KBDataSolutions\Sdk\Invoice\DecimalAmount;
use KBDataSolutions\Sdk\Invoice\Request\InvoiceLine;
use PHPUnit\Framework\TestCase;

final class InvoiceLineTest extends TestCase
{
    private function makeLine(string $quantity = '1', string $vatRate = '21'): InvoiceLine
    {
        return new InvoiceLine(
            externalId: '1',
            description: 'Widget',
            sku: 'WID-1',
            quantity: $quantity,
            unitPrice: new DecimalAmount('10.00'),
            netAmount: new DecimalAmount('10.00'),
            vatAmount: new DecimalAmount('2.10'),
            grossAmount: new DecimalAmount('12.10'),
            vatRate: $vatRate,
        );
    }

    public function test_to_array_maps_all_fields_to_the_wire_format(): void
    {
        self::assertSame([
            'external_id' => '1',
            'description' => 'Widget',
            'sku' => 'WID-1',
            'quantity' => '1',
            'unit_price' => '10.00',
            'net_amount' => '10.00',
            'vat_amount' => '2.10',
            'gross_amount' => '12.10',
            'vat_rate' => '21',
        ], $this->makeLine()->toArray());
    }

    public function test_description_must_not_be_empty(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new InvoiceLine('1', ' ', null, '1', new DecimalAmount('1'), new DecimalAmount('1'), new DecimalAmount('0'), new DecimalAmount('1'), '0');
    }

    public function test_quantity_must_be_positive(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->makeLine(quantity: '0');
    }

    public function test_quantity_must_not_exceed_one_million(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->makeLine(quantity: '1000001');
    }

    public function test_quantity_allows_up_to_four_decimals(): void
    {
        self::assertSame('1.2345', $this->makeLine(quantity: '1.2345')->quantity);
    }

    public function test_vat_rate_must_not_exceed_100(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->makeLine(vatRate: '100.1');
    }

    public function test_vat_rate_of_zero_is_allowed(): void
    {
        self::assertSame('0', $this->makeLine(vatRate: '0')->vatRate);
    }
}
