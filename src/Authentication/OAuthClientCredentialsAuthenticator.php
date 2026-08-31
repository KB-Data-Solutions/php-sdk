<?php

declare(strict_types=1);

namespace KBDataSolutions\Sdk\Authentication;

use DateInterval;
use KBDataSolutions\Sdk\Contract\Clock;
use KBDataSolutions\Sdk\Exception\AuthenticationException;
use KBDataSolutions\Sdk\Http\Transport;
use KBDataSolutions\Sdk\Http\TransportRequest;
use KBDataSolutions\Sdk\Http\TransportResponse;

/**
 * Authenticates against the KB Data Solutions API using the OAuth2 Client Credentials
 * grant. Talks to the Transport directly rather than through ApiClient: the token
 * endpoint is form-encoded (not JSON), has no bearer token to attach, and returns a
 * standard OAuth2 error body ({"error", "error_description"}) rather than the domain
 * API's {"error": {"code", "message", "details"}} envelope that ErrorMapper expects.
 */
final class OAuthClientCredentialsAuthenticator implements Authenticator
{
    private const TOKEN_PATH = '/oauth/token';

    /**
     * @param list<Scope> $scopes
     */
    public function __construct(
        private readonly Transport $transport,
        private readonly Credentials $credentials,
        private readonly TokenCache $tokenCache,
        private readonly Clock $clock,
        private readonly array $scopes,
    ) {
    }

    public function accessToken(): AccessToken
    {
        $cached = $this->tokenCache->get();

        if ($cached !== null && !$cached->isExpired($this->clock->now())) {
            return $cached;
        }

        return $this->forceRefresh();
    }

    public function forceRefresh(): AccessToken
    {
        $token = $this->requestToken();
        $this->tokenCache->set($token);

        return $token;
    }

    private function requestToken(): AccessToken
    {
        $body = http_build_query([
            'grant_type' => 'client_credentials',
            'client_id' => $this->credentials->clientId,
            'client_secret' => $this->credentials->clientSecret,
            'scope' => implode(' ', array_map(static fn (Scope $scope): string => $scope->value, $this->scopes)),
        ], '', '&', \PHP_QUERY_RFC3986);

        $response = $this->transport->send(new TransportRequest(
            'POST',
            self::TOKEN_PATH,
            [
                'Content-Type' => 'application/x-www-form-urlencoded',
                'Accept' => 'application/json',
            ],
            $body,
        ));

        if ($response->statusCode >= 400) {
            throw $this->toAuthenticationException($response);
        }

        return $this->mapToken($response);
    }

    private function toAuthenticationException(TransportResponse $response): AuthenticationException
    {
        $decoded = json_decode($response->body, true);
        $errorCode = \is_array($decoded) && \is_string($decoded['error'] ?? null) ? $decoded['error'] : null;
        $errorDescription = \is_array($decoded) && \is_string($decoded['error_description'] ?? null)
            ? $decoded['error_description']
            : \sprintf('OAuth token request failed with HTTP status %d.', $response->statusCode);

        return new AuthenticationException($errorDescription, $errorCode, $response->requestId());
    }

    private function mapToken(TransportResponse $response): AccessToken
    {
        $decoded = json_decode($response->body, true);

        if (!\is_array($decoded)) {
            throw new AuthenticationException('OAuth token response was not valid JSON.', null, $response->requestId());
        }

        $accessToken = $decoded['access_token'] ?? null;
        $expiresIn = $decoded['expires_in'] ?? null;

        if (!\is_string($accessToken) || $accessToken === '' || !\is_int($expiresIn)) {
            throw new AuthenticationException(
                'OAuth token response was missing a valid "access_token" or "expires_in".',
                null,
                $response->requestId(),
            );
        }

        return new AccessToken(
            $accessToken,
            $this->clock->now()->add(new DateInterval(\sprintf('PT%dS', $expiresIn))),
            $this->scopes,
        );
    }
}
