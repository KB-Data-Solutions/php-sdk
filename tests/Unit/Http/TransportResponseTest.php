<?php

declare(strict_types=1);

namespace KBDataSolutions\Sdk\Tests\Unit\Http;

use KBDataSolutions\Sdk\Http\TransportResponse;
use PHPUnit\Framework\TestCase;

final class TransportResponseTest extends TestCase
{
    public function test_header_lookup_is_case_insensitive(): void
    {
        $response = new TransportResponse(200, ['X-Request-Id' => 'req-1'], '{}');

        self::assertSame('req-1', $response->header('x-request-id'));
        self::assertSame('req-1', $response->header('X-REQUEST-ID'));
    }

    public function test_header_returns_null_when_absent(): void
    {
        $response = new TransportResponse(200, [], '{}');

        self::assertNull($response->header('X-Request-Id'));
    }

    public function test_request_id_reads_x_request_id_header(): void
    {
        $response = new TransportResponse(200, ['X-Request-Id' => 'req-2'], '{}');

        self::assertSame('req-2', $response->requestId());
    }
}
