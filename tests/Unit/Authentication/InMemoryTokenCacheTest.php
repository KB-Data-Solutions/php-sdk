<?php

declare(strict_types=1);

namespace KBDataSolutions\Sdk\Tests\Unit\Authentication;

use DateTimeImmutable;
use KBDataSolutions\Sdk\Authentication\AccessToken;
use KBDataSolutions\Sdk\Authentication\InMemoryTokenCache;
use PHPUnit\Framework\TestCase;

final class InMemoryTokenCacheTest extends TestCase
{
    public function test_returns_null_when_nothing_cached(): void
    {
        self::assertNull((new InMemoryTokenCache())->get());
    }

    public function test_returns_the_token_that_was_set(): void
    {
        $cache = new InMemoryTokenCache();
        $token = new AccessToken('token', new DateTimeImmutable('+1 hour'), []);

        $cache->set($token);

        self::assertSame($token, $cache->get());
    }

    public function test_clear_removes_the_cached_token(): void
    {
        $cache = new InMemoryTokenCache();
        $cache->set(new AccessToken('token', new DateTimeImmutable('+1 hour'), []));

        $cache->clear();

        self::assertNull($cache->get());
    }
}
