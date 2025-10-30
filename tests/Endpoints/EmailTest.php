<?php

namespace SenderNet\Tests\Endpoints;

use Http\Mock\Client;
use SenderNet\Common\HttpLayer;
use SenderNet\Endpoints\Email;
use SenderNet\Exceptions\SenderNetAssertException;
use SenderNet\Exceptions\SenderNetRateLimitException;
use SenderNet\Exceptions\SenderNetValidationException;
use SenderNet\Helpers\Arr;
use SenderNet\Helpers\Builder\Attachment;
use SenderNet\Helpers\Builder\UrlAttachment;
use SenderNet\Helpers\Builder\Base64Attachment;
use SenderNet\Helpers\Builder\EmailParams;
use SenderNet\Helpers\Builder\Personalization;
use SenderNet\Helpers\Builder\Recipient;
use SenderNet\Tests\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

class EmailTest extends TestCase
{
    protected Email $email;
    protected ResponseInterface $defaultResponse;

    public function setUp(): void
    {
        parent::setUp();

        $this->client = new Client();

        $this->email = new Email(new HttpLayer(self::OPTIONS, $this->client), self::OPTIONS);

        $this->defaultResponse = $this->createMock(ResponseInterface::class);
        $this->defaultResponse->method('getStatusCode')->willReturn(200);
    }

    public function test_send_request_validation_error(): void
    {
        $this->expectException(SenderNetValidationException::class);
        $this->expectExceptionMessage('Validation Error');

        $responseBody = $this->createMock(StreamInterface::class);
        $responseBody->method('getContents')->willReturn('{"message": "Validation Error", "errors": []}');

        $validationErrorResponse = $this->createMock(ResponseInterface::class);
        $validationErrorResponse->method('getStatusCode')->willReturn(422);
        $validationErrorResponse->method('getBody')->willReturn($responseBody);
        $validationErrorResponse->method('getHeaders')->willReturn([]);
        $this->client->addResponse($validationErrorResponse);

        $emailParams = (new EmailParams())
            ->setFrom('test@example.com')
            ->setFromName('Sender')
            ->setRecipients([
                [
                    'wrong recipient'
                ]
            ])
            ->setSubject('Subject')
            ->setText('TEXT');

        $this->email->send($emailParams);
    }


    /**
     * @dataProvider validEmailParamsProvider
     * @param EmailParams $emailParams
     * @throws SenderNetAssertException
     * @throws \JsonException
     * @throws \Psr\Http\Client\ClientExceptionInterface
     */
    #[DataProvider('validEmailParamsProvider')]
    public function test_send_email(EmailParams $emailParams): void
    {
        $response = $this->createMock(ResponseInterface::class);
        $response->method('getStatusCode')->willReturn(200);

        $this->client->addResponse($response);

        $response = $this->email->send($emailParams);

        $request = $this->client->getLastRequest();
        $request_body = json_decode((string) $request->getBody(), true);

        self::assertEquals('POST', $request->getMethod());
        self::assertEquals('/v2/message/send', $request->getUri()->getPath());
        self::assertEquals(200, $response['status_code']);

        self::assertEquals($emailParams->getFrom(), Arr::get($request_body, 'from.email'));
        self::assertEquals($emailParams->getFromName(), Arr::get($request_body, 'from.name'));
        self::assertEquals($emailParams->getReplyTo(), Arr::get($request_body, 'reply_to.email'));
        self::assertEquals($emailParams->getReplyToName(), Arr::get($request_body, 'reply_to.name'));

        $recipients = $emailParams->getRecipients();
        if (!empty($recipients)) {
            $firstRecipient = !is_array($recipients[0]) ? $recipients[0]->toArray() : $recipients[0];
            self::assertEquals($firstRecipient['name'], Arr::get($request_body, "to.name"));
            self::assertEquals($firstRecipient['email'], Arr::get($request_body, "to.email"));
        }
        self::assertEquals($emailParams->getSubject(), Arr::get($request_body, 'subject'));
        self::assertEquals($emailParams->getHtml(), Arr::get($request_body, 'html'));
        self::assertEquals($emailParams->getText(), Arr::get($request_body, 'text'));

        if (!empty($emailParams->getAttachments())) {
            $attachments = Arr::get($request_body, 'attachments') ?? [];
            foreach ($emailParams->getAttachments() as $attachment) {
                if ($attachment instanceof Attachment) {
                    self::assertEquals($attachment->getValue(), $attachments[$attachment->getFilename()]);
                }
            }
        }

        self::assertCount(count($emailParams->getHeaders()), Arr::get($request_body, 'headers') ?? []);
        foreach ($emailParams->getHeaders() as $key => $header) {
            $header = !is_array($header) ? $header->toArray() : $header;
            self::assertEquals($header['name'], Arr::get($request_body, "headers.$key.name"));
            self::assertEquals($header['value'], Arr::get($request_body, "headers.$key.value"));
        }

        self::assertCount(count($emailParams->getVariables()), Arr::get($request_body, 'variables') ?? []);
    }

    /**
     * @dataProvider invalidEmailParamsProvider
     * @param EmailParams $emailParams
     * @throws SenderNetAssertException
     * @throws \JsonException
     * @throws \Psr\Http\Client\ClientExceptionInterface
     */
    #[DataProvider('invalidEmailParamsProvider')]
    public function test_send_email_with_errors(EmailParams $emailParams)
    {
        $this->expectException(SenderNetAssertException::class);

        $httpLayer = $this->createMock(HttpLayer::class);
        $httpLayer->method('post')
            ->withAnyParameters()
            ->willReturn([]);

        (new Email($httpLayer, self::OPTIONS))->send($emailParams);
    }

    public static function validEmailParamsProvider(): array
    {
        return [
            'simple request' => [
                (new EmailParams())
                    ->setFrom('test@example.com')
                    ->setFromName('Sender')
                    ->setReplyTo('reply-to@example.com')
                    ->setReplyToName('Reply To')
                    ->setRecipients([
                        [
                            'name' => 'Recipient',
                            'email' => 'recipient@example.com',
                        ]
                    ])
                    ->setSubject('Subject')
                    ->setHtml('HTML')
                    ->setText('Text'),
            ],
            'using recipients helper' => [
                (new EmailParams())
                    ->setFrom('test@example.com')
                    ->setFromName('Sender')
                    ->setRecipients([
                        new Recipient('recipient@example.com', 'Recipient')
                    ])
                    ->setSubject('Subject')
                    ->setText('TEXT'),
            ],
            'using attachments helper' => [
                (new EmailParams())
                    ->setFrom('test@example.com')
                    ->setFromName('Sender')
                    ->setRecipients([
                        [
                            'name' => 'Recipient',
                            'email' => 'recipient@example.com',
                        ]
                    ])
                    ->setSubject('Subject')
                    ->setHtml('HTML')
                    ->setText('Text')
                    ->setAttachments([
                        new UrlAttachment('file.jpg', 'https://example.com/files/file.jpg'),
                    ]),
            ],
            'without html' => [
                (new EmailParams())
                    ->setFrom('test@example.com')
                    ->setFromName('Sender')
                    ->setReplyTo('reply-to@example.com')
                    ->setReplyToName('Reply To')
                    ->setRecipients([
                        [
                            'name' => 'Recipient',
                            'email' => 'recipient@example.com',
                        ]
                    ])
                    ->setSubject('Subject')
                    ->setText('Text'),
            ],
            'with custom headers' => [
                (new EmailParams())
                    ->setFrom('test@example.com')
                    ->setFromName('Sender')
                    ->setReplyTo('reply-to@example.com')
                    ->setReplyToName('Reply To')
                    ->setRecipients([
                        [
                            'name' => 'Recipient',
                            'email' => 'recipient@example.com',
                        ]
                    ])
                    ->setSubject('Subject')
                    ->setHtml('HTML')
                    ->setText('Text')
                    ->setHeaders([
                        [
                          'name' => 'Custom-Header-1',
                          'value' => 'Value 1',
                        ]
                    ])
            ],
            'with variables' => [
                (new EmailParams())
                    ->setFrom('test@example.com')
                    ->setFromName('Sender')
                    ->setRecipients([
                        [
                            'name' => 'Recipient',
                            'email' => 'recipient@example.com',
                        ]
                    ])
                    ->setSubject('Subject')
                    ->setHtml('HTML {{ name }}')
                    ->setText('Text')
                    ->setVariables([
                        'name' => 'John Doe',
                    ]),
            ],
            'with multiple attachments' => [
                (new EmailParams())
                    ->setFrom('test@example.com')
                    ->setFromName('Sender')
                    ->setRecipients([
                        new Recipient('recipient@example.com', 'Recipient')
                    ])
                    ->setSubject('Multiple Attachments')
                    ->setHtml('<h1>Files Attached</h1>')
                    ->setText('See attached files')
                    ->setAttachments([
                        new UrlAttachment('document.pdf', 'https://cdn.example.com/docs/document.pdf'),
                        new UrlAttachment('image.jpg', 'https://cdn.example.com/images/photo.jpg'),
                        new UrlAttachment('spreadsheet.xlsx', 'https://storage.example.com/files/data.xlsx'),
                    ]),
            ],
            'advanced with all features' => [
                (new EmailParams())
                    ->setFrom('info@sender.net')
                    ->setFromName('Sender Team')
                    ->setReplyTo('reply@sender.net')
                    ->setReplyToName('Reply Contact')
                    ->setRecipients([
                        new Recipient('recipient@example.com', 'Recipient Name')
                    ])
                    ->setSubject('Advanced Email with All Features')
                    ->setHtml('<h1>Advanced Email Test</h1><p>This email demonstrates <strong>all capabilities</strong>: Custom From/Reply-To, HTML + Text content, Custom headers, Variables, and Attachments.</p>')
                    ->setText('This is the plain text version of the advanced email from Sender.')
                    ->setHeaders([
                        ['name' => 'X-Custom-Header', 'value' => 'custom_value_1'],
                        ['name' => 'X-Test-Variable', 'value' => 'test_value'],
                    ])
                    ->setVariables([
                        'user_name' => 'John Doe',
                        'account_type' => 'Premium',
                    ])
                    ->setAttachments([
                        new UrlAttachment('invoice.pdf', 'https://cdn.sender.net/invoices/invoice-001.pdf'),
                        new UrlAttachment('terms.pdf', 'https://cdn.sender.net/legal/terms.pdf'),
                    ]),
            ],
        ];
    }

    public static function invalidEmailParamsProvider(): array
    {
        return [
            'html and text missing' => [
                (new EmailParams())
                    ->setFrom('test@example.com')
                    ->setFromName('Sender')
                    ->setRecipients([
                        new Recipient('recipient@example.com', 'Recipient')
                    ])
                    ->setSubject('Subject')
            ],
            'from is required' => [
                (new EmailParams())
                    ->setFromName('Sender')
                    ->setReplyTo('reply-to@example.com')
                    ->setReplyToName('Reply To')
                    ->setRecipients([
                        [
                            'name' => 'Recipient',
                            'email' => 'recipient@example.com',
                        ]
                    ])
                    ->setSubject('Subject')
                    ->setHtml('HTML')
            ],
            'from name is required' => [
                (new EmailParams())
                    ->setFrom('sender@example.com')
                    ->setReplyTo('reply-to@example.com')
                    ->setReplyToName('Reply To')
                    ->setRecipients([
                        [
                            'name' => 'Recipient',
                            'email' => 'recipient@example.com',
                        ]
                    ])
                    ->setSubject('Subject')
                    ->setHtml('HTML')
            ],
            'subject is required' => [
                (new EmailParams())
                    ->setFrom('sender@example.com')
                    ->setFromName('Sender')
                    ->setReplyTo('reply-to@example.com')
                    ->setReplyToName('Reply To')
                    ->setRecipients([
                        [
                            'name' => 'Recipient',
                            'email' => 'recipient@example.com',
                        ]
                    ])
                    ->setHtml('HTML')
            ],
            'at least one recipients' => [
                (new EmailParams())
                    ->setFrom('sender@example.com')
                    ->setFromName('Sender')
                    ->setReplyTo('reply-to@example.com')
                    ->setReplyToName('Reply To')
                    ->setRecipients([])
                    ->setSubject('Subject')
                    ->setHtml('HTML')
            ],
            'multiple primary recipients not allowed' => [
                (new EmailParams())
                    ->setFrom('sender@example.com')
                    ->setFromName('Sender')
                    ->setRecipients([
                        new Recipient('recipient1@example.com', 'Recipient 1'),
                        new Recipient('recipient2@example.com', 'Recipient 2'),
                    ])
                    ->setSubject('Subject')
                    ->setHtml('HTML')
            ],
        ];
    }

    public function test_should_throw_exception_on_rate_limit(): void
    {
        $this->expectException(SenderNetRateLimitException::class);

        $responseBody = $this->createMock(StreamInterface::class);
        $responseBody->method('getContents')->willReturn('{"message": "Too Many Attempts"}');

        $validationErrorResponse = $this->createMock(ResponseInterface::class);
        $validationErrorResponse->method('getStatusCode')->willReturn(429);
        $validationErrorResponse->method('getHeaders')->willReturn([]);
        $this->client->addResponse($validationErrorResponse);

        $emailParams = (new EmailParams())
            ->setFrom('test@example.com')
            ->setFromName('Sender')
            ->setRecipients([
                [
                    'wrong recipient'
                ]
            ])
            ->setSubject('Subject')
            ->setText('TEXT');

        $this->email->send($emailParams);
    }
}
