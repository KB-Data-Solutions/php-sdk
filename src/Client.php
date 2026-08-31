<?php

declare(strict_types=1);

namespace KBDataSolutions\Sdk;

use KBDataSolutions\Sdk\Authentication\Authenticator;
use KBDataSolutions\Sdk\Authentication\Credentials;
use KBDataSolutions\Sdk\Authentication\InMemoryTokenCache;
use KBDataSolutions\Sdk\Authentication\OAuthClientCredentialsAuthenticator;
use KBDataSolutions\Sdk\Authentication\Scope;
use KBDataSolutions\Sdk\Client\ApiClient;
use KBDataSolutions\Sdk\Contract\Clock;
use KBDataSolutions\Sdk\Contract\Sleeper;
use KBDataSolutions\Sdk\Debtor\DebtorsResource;
use KBDataSolutions\Sdk\Http\DiscoveryTransportFactory;
use KBDataSolutions\Sdk\Http\Transport;
use KBDataSolutions\Sdk\Invoice\InvoicesResource;
use KBDataSolutions\Sdk\Support\SystemClock;
use KBDataSolutions\Sdk\Support\SystemSleeper;

final class Client
{
    public function __construct(
        private readonly ApiClient $apiClient,
        private readonly Sleeper $sleeper,
    ) {
    }

    public static function create(
        string $clientId,
        string $clientSecret,
        string $baseUri,
        ?Transport $transport = null,
        ?Authenticator $authenticator = null,
        ?Clock $clock = null,
        ?Sleeper $sleeper = null,
    ): self {
        $clock ??= new SystemClock();
        $sleeper ??= new SystemSleeper();
        $transport ??= DiscoveryTransportFactory::create($baseUri);
        $authenticator ??= new OAuthClientCredentialsAuthenticator(
            $transport,
            new Credentials($clientId, $clientSecret),
            new InMemoryTokenCache(),
            $clock,
            [Scope::DebtorsWrite, Scope::InvoicesWrite, Scope::IntegrationRead],
        );

        return new self(new ApiClient($transport, $authenticator), $sleeper);
    }

    public function debtors(): DebtorsResource
    {
        return new DebtorsResource($this->apiClient);
    }

    public function invoices(): InvoicesResource
    {
        return new InvoicesResource($this->apiClient, $this->sleeper);
    }
}
