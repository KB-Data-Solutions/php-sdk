<?php

declare(strict_types=1);

namespace KBDataSolutions\Sdk\Tests\Unit\Debtor\Request;

use InvalidArgumentException;
use KBDataSolutions\Sdk\Debtor\DebtorType;
use KBDataSolutions\Sdk\Debtor\Request\CreateDebtorRequest;
use PHPUnit\Framework\TestCase;

final class CreateDebtorRequestTest extends TestCase
{
    public function test_to_array_maps_all_fields_to_the_wire_format(): void
    {
        $request = new CreateDebtorRequest(
            externalCustomerId: 'cust-1',
            oneTime: false,
            type: DebtorType::Business,
            country: 'NL',
            companyName: 'Acme B.V.',
            firstName: 'Jane',
            lastName: 'Doe',
            email: 'jane@example.test',
            vatNumber: 'NL123456789B01',
            address: 'Main Street 1',
            postalCode: '1234AB',
            city: 'Amsterdam',
        );

        self::assertSame([
            'external_customer_id' => 'cust-1',
            'one_time' => false,
            'type' => 'business',
            'company_name' => 'Acme B.V.',
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'email' => 'jane@example.test',
            'vat_number' => 'NL123456789B01',
            'country' => 'NL',
            'address' => 'Main Street 1',
            'postal_code' => '1234AB',
            'city' => 'Amsterdam',
        ], $request->toArray());
    }

    public function test_a_one_time_consumer_debtor_does_not_require_an_external_customer_id(): void
    {
        $request = new CreateDebtorRequest(
            externalCustomerId: null,
            oneTime: true,
            type: DebtorType::Consumer,
            country: 'NL',
        );

        self::assertNull($request->externalCustomerId);
    }

    public function test_a_non_one_time_debtor_requires_an_external_customer_id(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new CreateDebtorRequest(
            externalCustomerId: null,
            oneTime: false,
            type: DebtorType::Consumer,
            country: 'NL',
        );
    }

    public function test_a_business_debtor_requires_a_company_name(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new CreateDebtorRequest(
            externalCustomerId: 'cust-1',
            oneTime: false,
            type: DebtorType::Business,
            country: 'NL',
        );
    }

    public function test_country_must_not_be_empty(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new CreateDebtorRequest(
            externalCustomerId: 'cust-1',
            oneTime: false,
            type: DebtorType::Consumer,
            country: '  ',
        );
    }
}
