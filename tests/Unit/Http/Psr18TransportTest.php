<?php

declare(strict_types=1);

namespace KBDataSolutions\Sdk\Tests\Unit\Http;

use KBDataSolutions\Sdk\Exception\TransportException;
use KBDataSolutions\Sdk\Http\Psr18Transport;
use KBDataSolutions\Sdk\Http\TransportRequest;
use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7\Response;
use PHPUnit\Framework\TestCase;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use RuntimeException;

final class Psr18TransportTest extends TestCase
{
    public function test_send_maps_a_successful_psr7_response(): void
    {
        $client = new class () implements ClientInterface {
            public ?RequestInterface $capturedRequest = null;

            public function sendRequest(RequestInterface $request): ResponseInterface
            {
                $this->capturedRequest = $request;

                return new Response(202, ['X-Request-Id' => 'req-123'], '{"data":{"id":"1"}}');
            }
        };

        $factory = new Psr17Factory();
        $transport = new Psr18Transport($client, $factory, $factory, 'https://app.kbdatasolutions.nl/');

        $response = $transport->send(new TransportRequest('POST', '/api/v1/debtors', ['Authorization' => 'Bearer token'], '{"type":"consumer"}'));

        self::assertSame(202, $response->statusCode);
        self::assertSame('{"data":{"id":"1"}}', $response->body);
        self::assertSame('req-123', $response->requestId());
        self::assertSame('https://app.kbdatasolutions.nl/api/v1/debtors', (string) $client->capturedRequest?->getUri());
        self::assertSame('Bearer token', $client->capturedRequest?->getHeaderLine('Authorization'));
    }

    public function test_send_wraps_client_exceptions_in_transport_exception(): void
    {
        $client = new class () implements ClientInterface {
            public function sendRequest(RequestInterface $request): ResponseInterface
            {
                throw new class () extends RuntimeException implements ClientExceptionInterface {
                };
            }
        };

        $factory = new Psr17Factory();
        $transport = new Psr18Transport($client, $factory, $factory, 'https://app.kbdatasolutions.nl');

        $this->expectException(TransportException::class);

        $transport->send(new TransportRequest('GET', '/api/v1/debtors/1'));
    }
}
