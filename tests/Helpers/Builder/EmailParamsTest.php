<?php

namespace SenderNet\Tests\Helpers\Builder;

use SenderNet\Helpers\Builder\Attachment;
use SenderNet\Helpers\Builder\UrlAttachment;
use SenderNet\Helpers\Builder\Base64Attachment;
use SenderNet\Helpers\Builder\EmailParams;
use SenderNet\Tests\TestCase;

class EmailParamsTest extends TestCase
{
    public function test_set_attachments_accepts_url_attachments(): void
    {
        $params = new EmailParams();
        $attachments = [
            new UrlAttachment('file1.pdf', 'https://example.com/file1.pdf'),
            new UrlAttachment('file2.pdf', 'https://example.com/file2.pdf'),
        ];

        $params->setAttachments($attachments);

        self::assertEquals($attachments, $params->getAttachments());
        self::assertTrue($params->hasUrlAttachments());
        self::assertFalse($params->hasBase64Attachments());
    }

    public function test_set_attachments_accepts_base64_attachments(): void
    {
        $params = new EmailParams();
        $attachments = [
            new Base64Attachment('file.txt', base64_encode('content')),
        ];

        $params->setAttachments($attachments);

        self::assertEquals($attachments, $params->getAttachments());
        self::assertTrue($params->hasBase64Attachments());
        self::assertFalse($params->hasUrlAttachments());
    }

    public function test_set_attachments_allows_mixed_types(): void
    {
        $params = new EmailParams();
        $attachments = [
            new UrlAttachment('file1.pdf', 'https://example.com/file1.pdf'),
            new Base64Attachment('file2.txt', base64_encode('content')),
        ];

        $params->setAttachments($attachments);

        self::assertEquals($attachments, $params->getAttachments());
        self::assertTrue($params->hasUrlAttachments());
        self::assertTrue($params->hasBase64Attachments());
    }

    public function test_has_base64_attachments_returns_false_when_empty(): void
    {
        $params = new EmailParams();

        self::assertFalse($params->hasBase64Attachments());
    }

    public function test_has_url_attachments_returns_false_when_empty(): void
    {
        $params = new EmailParams();

        self::assertFalse($params->hasUrlAttachments());
    }
}
