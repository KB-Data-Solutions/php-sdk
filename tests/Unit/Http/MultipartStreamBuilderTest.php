<?php

declare(strict_types=1);

namespace KBDataSolutions\Sdk\Tests\Unit\Http;

use KBDataSolutions\Sdk\Http\MultipartFile;
use KBDataSolutions\Sdk\Http\MultipartStreamBuilder;
use PHPUnit\Framework\TestCase;

final class MultipartStreamBuilderTest extends TestCase
{
    public function test_builds_body_with_fields_only(): void
    {
        $result = (new MultipartStreamBuilder())->build(['payload' => '{"a":1}']);

        self::assertMatchesRegularExpression('/^multipart\/form-data; boundary=[0-9a-f]{32}$/', $result['contentType']);
        self::assertStringContainsString('Content-Disposition: form-data; name="payload"', $result['body']);
        self::assertStringContainsString('{"a":1}', $result['body']);
    }

    public function test_builds_body_with_field_and_file(): void
    {
        $file = new MultipartFile('document', 'invoice.pdf', '%PDF-1.4 fake', 'application/pdf');

        $result = (new MultipartStreamBuilder())->build(['payload' => '{"a":1}'], $file);

        self::assertStringContainsString('name="document"; filename="invoice.pdf"', $result['body']);
        self::assertStringContainsString('Content-Type: application/pdf', $result['body']);
        self::assertStringContainsString('%PDF-1.4 fake', $result['body']);
    }

    public function test_body_ends_with_closing_boundary(): void
    {
        $result = (new MultipartStreamBuilder())->build(['payload' => '{}']);
        $boundary = substr($result['contentType'], strlen('multipart/form-data; boundary='));

        self::assertStringEndsWith("--{$boundary}--\r\n", $result['body']);
    }
}
