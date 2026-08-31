<?php

declare(strict_types=1);

namespace KBDataSolutions\Sdk\Invoice\Request;

use InvalidArgumentException;

final readonly class DocumentUpload
{
    private const MAX_SIZE_BYTES = 10 * 1024 * 1024;

    public function __construct(
        public string $filename,
        public string $contents,
        public string $mediaType = 'application/pdf',
    ) {
        if (\strlen($contents) > self::MAX_SIZE_BYTES) {
            throw new InvalidArgumentException(\sprintf(
                'Document "%s" is %d bytes, which exceeds the maximum of %d bytes (10 MB).',
                $filename,
                \strlen($contents),
                self::MAX_SIZE_BYTES,
            ));
        }
    }
}
