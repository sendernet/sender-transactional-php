<?php

namespace SenderNet\Tests\Helpers;

use SenderNet\Helpers\Builder\Attachment;
use SenderNet\Helpers\Builder\UrlAttachment;
use SenderNet\Helpers\Builder\Base64Attachment;
use SenderNet\Helpers\Builder\EmailParams;
use SenderNet\Helpers\EmailPayloadBuilder;
use SenderNet\Tests\TestCase;

class EmailPayloadBuilderTest extends TestCase
{
    public function test_from_email_params_filters_null_values(): void
    {
        $params = (new EmailParams())
            ->setFrom('sender@example.com')
            ->setFromName('Sender')
            ->setRecipients([
                ['email' => 'recipient@example.com', 'name' => 'Recipient'],
            ])
            ->setSubject('Subject')
            ->setText('Text body')
            ->setAttachments([
                new UrlAttachment('file.txt', 'https://example.com/files/file.txt'),
            ]);

        $payload = EmailPayloadBuilder::fromEmailParams($params);

        self::assertSame('sender@example.com', $payload['from']['email']);
        self::assertSame('Recipient', $payload['to']['name']);
        self::assertSame('Subject', $payload['subject']);
        self::assertSame('Text body', $payload['text']);
        self::assertArrayHasKey('attachments', $payload);
        self::assertSame('https://example.com/files/file.txt', $payload['attachments']['file.txt']);
        self::assertArrayNotHasKey('reply_to', $payload);
    }

    public function test_multiple_attachments_are_formatted_correctly(): void
    {
        $params = (new EmailParams())
            ->setFrom('sender@example.com')
            ->setFromName('Sender')
            ->setRecipients([
                ['email' => 'recipient@example.com', 'name' => 'Recipient'],
            ])
            ->setSubject('Multiple Attachments Test')
            ->setHtml('<h1>Test</h1>')
            ->setAttachments([
                new UrlAttachment('document.pdf', 'https://cdn.example.com/docs/document.pdf'),
                new UrlAttachment('image.jpg', 'https://cdn.example.com/images/photo.jpg'),
                new UrlAttachment('spreadsheet.xlsx', 'https://storage.example.com/files/data.xlsx'),
            ]);

        $payload = EmailPayloadBuilder::fromEmailParams($params);

        self::assertArrayHasKey('attachments', $payload);
        self::assertIsArray($payload['attachments']);
        self::assertCount(3, $payload['attachments']);
        self::assertSame('https://cdn.example.com/docs/document.pdf', $payload['attachments']['document.pdf']);
        self::assertSame('https://cdn.example.com/images/photo.jpg', $payload['attachments']['image.jpg']);
        self::assertSame('https://storage.example.com/files/data.xlsx', $payload['attachments']['spreadsheet.xlsx']);
    }

    public function test_no_attachments_excludes_attachments_field(): void
    {
        $params = (new EmailParams())
            ->setFrom('sender@example.com')
            ->setFromName('Sender')
            ->setRecipients([
                ['email' => 'recipient@example.com', 'name' => 'Recipient'],
            ])
            ->setSubject('No Attachments')
            ->setText('Simple email without attachments');

        $payload = EmailPayloadBuilder::fromEmailParams($params);

        self::assertArrayNotHasKey('attachments', $payload);
    }

    public function test_empty_attachments_array_excludes_attachments_field(): void
    {
        $params = (new EmailParams())
            ->setFrom('sender@example.com')
            ->setFromName('Sender')
            ->setRecipients([
                ['email' => 'recipient@example.com', 'name' => 'Recipient'],
            ])
            ->setSubject('Empty Attachments')
            ->setText('Email with empty attachments array')
            ->setAttachments([]);

        $payload = EmailPayloadBuilder::fromEmailParams($params);

        self::assertArrayNotHasKey('attachments', $payload);
    }

    public function test_base64_attachments_are_formatted_correctly(): void
    {
        $base64Content = base64_encode('PDF content');
        $params = (new EmailParams())
            ->setFrom('sender@example.com')
            ->setFromName('Sender')
            ->setRecipients([
                ['email' => 'recipient@example.com', 'name' => 'Recipient'],
            ])
            ->setSubject('Base64 Attachments Test')
            ->setHtml('<h1>Test</h1>')
            ->setAttachments([
                new Base64Attachment('document.pdf', $base64Content, 'application/pdf'),
                new Base64Attachment('data.txt', base64_encode('text content')),
            ]);

        $payload = EmailPayloadBuilder::fromEmailParams($params);

        self::assertArrayHasKey('attachments', $payload);
        self::assertIsArray($payload['attachments']);
        self::assertCount(2, $payload['attachments']);
        self::assertSame("base64://{$base64Content}", $payload['attachments']['document.pdf']);
        self::assertSame("base64://" . base64_encode('text content'), $payload['attachments']['data.txt']);
    }

    public function test_mixed_url_and_base64_attachments(): void
    {
        $base64Content = base64_encode('file content');
        $params = (new EmailParams())
            ->setFrom('sender@example.com')
            ->setFromName('Sender')
            ->setRecipients([
                ['email' => 'recipient@example.com', 'name' => 'Recipient'],
            ])
            ->setSubject('Mixed Attachments Test')
            ->setHtml('<h1>Test</h1>')
            ->setAttachments([
                new UrlAttachment('remote.pdf', 'https://example.com/file.pdf'),
                new Base64Attachment('local.txt', $base64Content),
            ]);

        $payload = EmailPayloadBuilder::fromEmailParams($params);

        self::assertArrayHasKey('attachments', $payload);
        self::assertCount(2, $payload['attachments']);
        self::assertSame('https://example.com/file.pdf', $payload['attachments']['remote.pdf']);
        self::assertSame("base64://{$base64Content}", $payload['attachments']['local.txt']);
    }

    public function test_advanced_email_with_all_features_including_attachments(): void
    {
        $params = (new EmailParams())
            ->setFrom('info@sender.net')
            ->setFromName('Sender Team')
            ->setReplyTo('reply@sender.net')
            ->setReplyToName('Reply Contact')
            ->setRecipients([
                ['email' => 'recipient@example.com', 'name' => 'Recipient Name'],
            ])
            ->setSubject('Advanced Email with All Features')
            ->setHtml('<h1>Advanced Email Test</h1><p>This email demonstrates <strong>all capabilities</strong>.</p>')
            ->setText('This is the plain text version of the advanced email.')
            ->setHeaders([
                ['name' => 'X-Custom-Header', 'value' => 'custom_value_1'],
                ['name' => 'X-Test-Variable', 'value' => 'test_value'],
            ])
            ->setVariables([
                'user_name' => 'John Doe',
                'account_type' => 'Premium',
                'expiry_date' => '2025-12-31',
            ])
            ->setAttachments([
                new UrlAttachment('invoice.pdf', 'https://cdn.sender.net/invoices/2025/invoice-001.pdf'),
                new UrlAttachment('terms.pdf', 'https://cdn.sender.net/legal/terms.pdf'),
            ]);

        $payload = EmailPayloadBuilder::fromEmailParams($params);

        self::assertSame('info@sender.net', $payload['from']['email']);
        self::assertSame('Sender Team', $payload['from']['name']);
        self::assertSame('reply@sender.net', $payload['reply_to']['email']);
        self::assertSame('Reply Contact', $payload['reply_to']['name']);
        self::assertSame('recipient@example.com', $payload['to']['email']);
        self::assertSame('Recipient Name', $payload['to']['name']);
        self::assertSame('Advanced Email with All Features', $payload['subject']);
        self::assertStringContainsString('Advanced Email Test', $payload['html']);
        self::assertStringContainsString('plain text version', $payload['text']);

        self::assertCount(2, $payload['headers']);
        self::assertSame('X-Custom-Header', $payload['headers'][0]['name']);
        self::assertSame('custom_value_1', $payload['headers'][0]['value']);

        self::assertCount(3, $payload['variables']);
        self::assertSame('John Doe', $payload['variables']['user_name']);
        self::assertSame('Premium', $payload['variables']['account_type']);

        self::assertCount(2, $payload['attachments']);
        self::assertSame('https://cdn.sender.net/invoices/2025/invoice-001.pdf', $payload['attachments']['invoice.pdf']);
        self::assertSame('https://cdn.sender.net/legal/terms.pdf', $payload['attachments']['terms.pdf']);
    }
}
