<?php

declare(strict_types=1);

namespace KBDataSolutions\Sdk\Http;

/**
 * @phpstan-type BuiltMultipartBody array{body: string, contentType: string}
 */
final class MultipartStreamBuilder
{
    /**
     * @param array<string, string> $fields
     * @return BuiltMultipartBody
     */
    public function build(array $fields, ?MultipartFile $file = null): array
    {
        $boundary = bin2hex(random_bytes(16));
        $parts = [];

        foreach ($fields as $name => $value) {
            $parts[] = \sprintf(
                "--%s\r\nContent-Disposition: form-data; name=\"%s\"\r\n\r\n%s\r\n",
                $boundary,
                $name,
                $value,
            );
        }

        if ($file !== null) {
            $parts[] = \sprintf(
                "--%s\r\nContent-Disposition: form-data; name=\"%s\"; filename=\"%s\"\r\nContent-Type: %s\r\n\r\n%s\r\n",
                $boundary,
                $file->fieldName,
                $file->filename,
                $file->contentType,
                $file->contents,
            );
        }

        $parts[] = \sprintf("--%s--\r\n", $boundary);

        return [
            'body' => implode('', $parts),
            'contentType' => \sprintf('multipart/form-data; boundary=%s', $boundary),
        ];
    }
}
