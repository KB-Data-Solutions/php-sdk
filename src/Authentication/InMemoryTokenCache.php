<?php

declare(strict_types=1);

namespace KBDataSolutions\Sdk\Authentication;

final class InMemoryTokenCache implements TokenCache
{
    private ?AccessToken $token = null;

    public function get(): ?AccessToken
    {
        return $this->token;
    }

    public function set(AccessToken $token): void
    {
        $this->token = $token;
    }

    public function clear(): void
    {
        $this->token = null;
    }
}
