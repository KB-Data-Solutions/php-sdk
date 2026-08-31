<?php

declare(strict_types=1);

namespace KBDataSolutions\Sdk\Authentication;

interface TokenCache
{
    public function get(): ?AccessToken;

    public function set(AccessToken $token): void;

    public function clear(): void;
}
