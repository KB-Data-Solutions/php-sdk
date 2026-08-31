<?php

declare(strict_types=1);

namespace KBDataSolutions\Sdk\Authentication;

use KBDataSolutions\Sdk\Exception\AuthenticationException;

interface Authenticator
{
    /**
     * Returns a cached access token, fetching a new one if none is cached or the cached
     * one has expired.
     *
     * @throws AuthenticationException
     */
    public function accessToken(): AccessToken;

    /**
     * Always fetches a fresh access token, bypassing (and updating) the cache.
     *
     * @throws AuthenticationException
     */
    public function forceRefresh(): AccessToken;
}
