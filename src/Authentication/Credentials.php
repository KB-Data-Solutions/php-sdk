<?php

declare(strict_types=1);

namespace KBDataSolutions\Sdk\Authentication;

use SensitiveParameter;

final readonly class Credentials
{
    public function __construct(
        public string $clientId,
        #[SensitiveParameter]
        public string $clientSecret,
    ) {
    }
}
