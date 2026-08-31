<?php

declare(strict_types=1);

namespace KBDataSolutions\Sdk\Http;

final readonly class MultipartFile
{
    public function __construct(
        public string $fieldName,
        public string $filename,
        public string $contents,
        public string $contentType,
    ) {
    }
}
