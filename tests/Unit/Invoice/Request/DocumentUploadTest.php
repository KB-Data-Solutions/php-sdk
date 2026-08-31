<?php

declare(strict_types=1);

namespace KBDataSolutions\Sdk\Tests\Unit\Invoice\Request;

use InvalidArgumentException;
use KBDataSolutions\Sdk\Invoice\Request\DocumentUpload;
use PHPUnit\Framework\TestCase;

final class DocumentUploadTest extends TestCase
{
    public function test_accepts_a_document_within_the_size_limit(): void
    {
        $upload = new DocumentUpload('invoice.pdf', str_repeat('a', 1024));

        self::assertSame('invoice.pdf', $upload->filename);
        self::assertSame('application/pdf', $upload->mediaType);
    }

    public function test_rejects_a_document_over_ten_megabytes(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new DocumentUpload('invoice.pdf', str_repeat('a', 10 * 1024 * 1024 + 1));
    }
}
