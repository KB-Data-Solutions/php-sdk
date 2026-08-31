<?php

declare(strict_types=1);

namespace KBDataSolutions\Sdk\Http;

use Http\Discovery\Exception\NotFoundException;
use Http\Discovery\Psr17FactoryDiscovery;
use Http\Discovery\Psr18ClientDiscovery;
use KBDataSolutions\Sdk\Exception\TransportException;

final class DiscoveryTransportFactory
{
    public static function create(string $baseUri): Psr18Transport
    {
        try {
            $httpClient = Psr18ClientDiscovery::find();
            $requestFactory = Psr17FactoryDiscovery::findRequestFactory();
            $streamFactory = Psr17FactoryDiscovery::findStreamFactory();
        } catch (NotFoundException $exception) {
            throw new TransportException(
                'No PSR-18 HTTP client and/or PSR-17 factories could be discovered. '
                .'Install one (e.g. "composer require guzzlehttp/guzzle") or pass a Transport explicitly to Client::create().',
                $exception,
            );
        }

        return new Psr18Transport($httpClient, $requestFactory, $streamFactory, $baseUri);
    }
}
