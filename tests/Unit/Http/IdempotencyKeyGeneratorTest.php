<?php

declare(strict_types=1);

namespace KBDataSolutions\Sdk\Tests\Unit\Http;

use KBDataSolutions\Sdk\Http\IdempotencyKeyGenerator;
use PHPUnit\Framework\TestCase;

final class IdempotencyKeyGeneratorTest extends TestCase
{
    public function test_generates_a_valid_uuid_v4(): void
    {
        $key = IdempotencyKeyGenerator::generate();

        self::assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/',
            $key,
        );
    }

    public function test_generates_unique_values(): void
    {
        self::assertNotSame(IdempotencyKeyGenerator::generate(), IdempotencyKeyGenerator::generate());
    }
}
