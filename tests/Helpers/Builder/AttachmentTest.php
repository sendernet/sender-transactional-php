<?php

namespace SenderNet\Tests\Helpers\Builder;

use InvalidArgumentException;
use SenderNet\Helpers\Builder\Attachment;
use SenderNet\Helpers\Builder\UrlAttachment;
use SenderNet\Helpers\Builder\Base64Attachment;
use SenderNet\Tests\TestCase;

class AttachmentTest extends TestCase
{
    public function test_creates_url_attachment(): void
    {
        $attachment = new UrlAttachment('document.pdf', 'https://example.com/file.pdf');

        self::assertInstanceOf(UrlAttachment::class, $attachment);
        self::assertEquals('document.pdf', $attachment->getFilename());
        self::assertEquals('https://example.com/file.pdf', $attachment->getUrl());
        self::assertEquals('https://example.com/file.pdf', $attachment->getValue());
    }

    public function test_url_attachment_validates_url_format(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid URL provided');

        new UrlAttachment('document.pdf', 'not-a-valid-url');
    }

    public function test_url_attachment_requires_filename(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Filename cannot be empty');

        new UrlAttachment('', 'https://example.com/file.pdf');
    }

    public function test_url_attachment_requires_url(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('URL cannot be empty');

        new UrlAttachment('document.pdf', '');
    }

    public function test_creates_base64_attachment(): void
    {
        $base64 = base64_encode('test content');
        $attachment = new Base64Attachment('test.txt', $base64);

        self::assertInstanceOf(Base64Attachment::class, $attachment);
        self::assertEquals('test.txt', $attachment->getFilename());
        self::assertEquals($base64, $attachment->getBase64());
        self::assertEquals("base64://{$base64}", $attachment->getValue());
        self::assertNull($attachment->getMimeType());
    }

    public function test_base64_attachment_with_mime_type(): void
    {
        $base64 = base64_encode('test content');
        $attachment = new Base64Attachment('test.pdf', $base64, 'application/pdf');

        self::assertInstanceOf(Base64Attachment::class, $attachment);
        self::assertEquals('test.pdf', $attachment->getFilename());
        self::assertEquals($base64, $attachment->getBase64());
        self::assertEquals("base64://{$base64}", $attachment->getValue());
        self::assertEquals('application/pdf', $attachment->getMimeType());
    }

    public function test_base64_attachment_requires_filename(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Filename cannot be empty');

        new Base64Attachment('', base64_encode('content'));
    }

    public function test_base64_attachment_requires_base64_string(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Base64 string cannot be empty');

        new Base64Attachment('test.txt', '');
    }

    public function test_base64_attachment_validates_encoding(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid base64 encoding');

        new Base64Attachment('test.txt', 'not-valid-base64!!!');
    }

    public function test_url_attachment_does_not_have_base64_methods(): void
    {
        $attachment = new UrlAttachment('document.pdf', 'https://example.com/file.pdf');

        self::assertInstanceOf(UrlAttachment::class, $attachment);
        self::assertFalse(method_exists($attachment, 'getBase64'));
        self::assertFalse(method_exists($attachment, 'getMimeType'));
    }

    public function test_base64_attachment_does_not_have_url_method(): void
    {
        $attachment = new Base64Attachment('test.txt', base64_encode('content'));

        self::assertInstanceOf(Base64Attachment::class, $attachment);
        self::assertFalse(method_exists($attachment, 'getUrl'));
    }
}
