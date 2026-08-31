<?php

declare(strict_types=1);

namespace KBDataSolutions\Sdk\Http;

use KBDataSolutions\Sdk\Exception\TransportException;

interface Transport
{
    /**
     * @throws TransportException
     */
    public function send(TransportRequest $request): TransportResponse;
}
