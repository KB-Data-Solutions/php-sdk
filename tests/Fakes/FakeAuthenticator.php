<?php

declare(strict_types=1);

namespace KBDataSolutions\Sdk\Tests\Fakes;

use KBDataSolutions\Sdk\Authentication\AccessToken;
use KBDataSolutions\Sdk\Authentication\Authenticator;

final class FakeAuthenticator implements Authenticator
{
    private int $forceRefreshCalls = 0;

    public function __construct(private AccessToken $token)
    {
    }

    public function accessToken(): AccessToken
    {
        return $this->token;
    }

    public function forceRefresh(): AccessToken
    {
        ++$this->forceRefreshCalls;

        return $this->token;
    }

    public function setToken(AccessToken $token): void
    {
        $this->token = $token;
    }

    public function forceRefreshCalls(): int
    {
        return $this->forceRefreshCalls;
    }
}
