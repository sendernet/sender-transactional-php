<?php

namespace SenderNet\Tests\Laravel;

use Illuminate\Support\Arr;
use SenderNet\Endpoints\Email;
use SenderNet\Exceptions\SenderNetRateLimitException;
use SenderNet\Exceptions\SenderNetValidationException;
use SenderNet\Helpers\Builder\EmailParams;
use SenderNet\Laravel\SenderTransport;
use SenderNet\SenderNet;
use Mockery\MockInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;
use Symfony\Component\Mailer\Envelope;
use Symfony\Component\Mailer\Exception\TransportException;
use SenderNet\Helpers\Builder\Base64Attachment;

class SenderTransportTest extends TestCase
{
    protected SenderNet $sender;
    protected SenderTransport $transport;

    protected function setUp(): void
    {
        parent::setUp();

        $this->sender = new SenderNet([
            'api_key' => 'key',
            'host' => '',
            'protocol' => '',
            'api_path' => '',
        ]);


        $this->transport = new SenderTransport($this->sender);
    }

    public function test_basic_message_is_sent(): void
    {
        $response = [
            'response' => $this->mock(ResponseInterface::class, function (MockInterface $mock) {
                $mock->expects('getHeaderLine')->withArgs(['X-Message-Id'])->andReturn('messageId');

                $stream = $this->mock(StreamInterface::class, function (MockInterface $mock) {
                    $mock->expects('getContents')->withNoArgs()->andReturn('{"json":"value"}');
                });

                $mock->expects('getBody')->withNoArgs()->andReturn($stream);
            }),
        ];

        $emailParams = $this->partialMock(EmailParams::class, function (MockInterface $mock) {
            $mock->expects('setFrom')->withArgs(['test@example.com'])->andReturnSelf();
            $mock->expects('setFromName')->withArgs(['John Doe'])->andReturnSelf();
            $mock->expects('setRecipients')->withAnyArgs()->andReturnSelf();
            $mock->expects('setSubject')->withArgs(['Subject'])->andReturnSelf();
            $mock->expects('setText')->withArgs(['Here is the text message'])->andReturnSelf();
        });

        /** @noinspection PhpFieldAssignmentTypeMismatchInspection */
        $this->sender->email = $this->mock(Email::class,
            function (MockInterface $mock) use ($emailParams, $response) {
                $mock->allows('send')->withArgs([$emailParams])->andReturn($response);
            });

        $message = (new \Symfony\Component\Mime\Email())
            ->subject('Subject')
            ->from('John Doe <test@example.com>')
            ->to('test-receive@example.com')
            ->text('Here is the text message');

        $transport = new SenderTransport($this->sender);
        $sentMessage = $transport->send($message, Envelope::create($message));

        self::assertNotNull($sentMessage);

        $sentMessageString = $sentMessage->getMessage()->toString();

        self::assertStringContainsString('X-SenderNet-Message-Id: messageId', $sentMessageString);
        self::assertStringContainsString('X-SenderNet-Body: {"json":"value"}', $sentMessageString);
    }

    public function test_get_from(): void
    {
        $message = (new \Symfony\Component\Mime\Email())
            ->from('John Doe <test@example.com>');

        $getFrom = $this->callMethod($this->transport, 'getFrom', [$message]);

        self::assertEquals(['email' => 'test@example.com', 'name' => 'John Doe'], $getFrom);
    }

    public function test_get_reply_to(): void
    {
        $message = (new \Symfony\Component\Mime\Email())
            ->replyTo('John Doe <test@example.com>');

        $getReplyTo = $this->callMethod($this->transport, 'getReplyTo', [$message]);

        self::assertEquals(['email' => 'test@example.com', 'name' => 'John Doe'], $getReplyTo);
    }

    public function test_get_recipients(): void
    {
        $message = (new \Symfony\Component\Mime\Email())
            ->to('test-receive@example.com');

        $getTo = $this->callMethod($this->transport, 'getRecipients', ['to', $message]);

        self::assertEquals('test-receive@example.com', Arr::get(reset($getTo)->toArray(),
            'email'));
    }

    public function test_get_attachments_with_urls(): void
    {
        $message = (new \Symfony\Component\Mime\Email())
            ->attach('https://example.com/files/document.pdf', 'report.pdf')
            ->attach('https://cdn.example.com/images/photo.jpg', 'photo.jpg');

        $attachments = $this->callMethod($this->transport, 'getAttachments', [$message]);

        self::assertCount(2, $attachments);
        self::assertEquals('report.pdf', $attachments[0]->getFilename());
        self::assertEquals('https://example.com/files/document.pdf', $attachments[0]->getUrl());
        self::assertEquals('photo.jpg', $attachments[1]->getFilename());
        self::assertEquals('https://cdn.example.com/images/photo.jpg', $attachments[1]->getUrl());
    }

    public function test_get_attachments_converts_local_files(): void
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'test_');
        file_put_contents($tempFile, 'test content');

        try {
            $message = (new \Symfony\Component\Mime\Email())
                ->attachFromPath($tempFile, 'local-file.pdf');

            $attachments = $this->callMethod($this->transport, 'getAttachments', [$message]);

            self::assertCount(1, $attachments);
            self::assertEquals('local-file.pdf', $attachments[0]->getFilename());
            self::assertInstanceOf(Base64Attachment::class, $attachments[0]);
        } finally {
            if (file_exists($tempFile)) {
                unlink($tempFile);
            }
        }
    }

    public function test_missing_from_address_throws_exception(): void
    {
        $this->expectException(\Symfony\Component\Mailer\Exception\TransportException::class);
        $this->expectExceptionMessage('FROM address is required. Please set exactly one FROM address.');

        $message = (new \Symfony\Component\Mime\Email())
            ->subject('Test')
            ->to('recipient@test.com')
            ->text('Test content');

        $this->callMethod($this->transport, 'getFrom', [$message]);
    }

    public function test_multiple_from_addresses_throws_exception(): void
    {
        $this->expectException(\Symfony\Component\Mailer\Exception\TransportException::class);
        $this->expectExceptionMessage('Multiple FROM addresses are not supported. Found 2 addresses, but only one is allowed.');

        $message = (new \Symfony\Component\Mime\Email())
            ->subject('Test')
            ->from('first@test.com', 'second@test.com')
            ->to('recipient@test.com')
            ->text('Test content');

        $this->callMethod($this->transport, 'getFrom', [$message]);
    }

    public function test_multiple_reply_to_addresses_throws_exception(): void
    {
        $this->expectException(\Symfony\Component\Mailer\Exception\TransportException::class);
        $this->expectExceptionMessage('Multiple REPLY-TO addresses are not supported. Found 2 addresses, but only one is allowed.');

        $message = (new \Symfony\Component\Mime\Email())
            ->replyTo('first@test.com', 'second@test.com');

        $this->callMethod($this->transport, 'getReplyTo', [$message]);
    }

    public function test_empty_reply_to_returns_empty_array(): void
    {
        $message = (new \Symfony\Component\Mime\Email());

        $replyTo = $this->callMethod($this->transport, 'getReplyTo', [$message]);

        self::assertEquals(['email' => '', 'name' => ''], $replyTo);
    }

    public function test_validation_exception_bubbles_as_transport_exception(): void
    {
        $validationException = new SenderNetValidationException(
            new \GuzzleHttp\Psr7\Request('POST', 'https://api.sender.net/v2/message/send'),
            new \GuzzleHttp\Psr7\Response(422, ['Content-Type' => 'application/json'], json_encode([
                'message' => 'Validation failed',
                'errors' => ['field' => ['Required']],
            ]))
        );

        $emailMock = $this->mock(Email::class, function (MockInterface $mock) use ($validationException) {
            $mock->allows('send')->andThrow($validationException);
        });

        /** @noinspection PhpFieldAssignmentTypeMismatchInspection */
        $this->sender->email = $emailMock;

        $message = (new \Symfony\Component\Mime\Email())
            ->subject('Subject')
            ->from('Sender <sender@example.com>')
            ->to('recipient@example.com')
            ->text('Body');

        $envelope = Envelope::create($message);

        $this->expectException(TransportException::class);
        $this->expectExceptionMessage('Validation failed');

        $this->transport->send($message, $envelope);
    }

    public function test_rate_limit_exception_bubbles_as_transport_exception(): void
    {
        $rateLimitException = new SenderNetRateLimitException(
            new \GuzzleHttp\Psr7\Request('GET', 'https://api.sender.net/v2/resource'),
            new \GuzzleHttp\Psr7\Response(429, ['Retry-After' => '60'])
        );

        $emailMock = $this->mock(Email::class, function (MockInterface $mock) use ($rateLimitException) {
            $mock->allows('send')->andThrow($rateLimitException);
        });

        /** @noinspection PhpFieldAssignmentTypeMismatchInspection */
        $this->sender->email = $emailMock;

        $message = (new \Symfony\Component\Mime\Email())
            ->subject('Subject')
            ->from('Sender <sender@example.com>')
            ->to('recipient@example.com')
            ->text('Body');

        $envelope = Envelope::create($message);

        $this->expectException(TransportException::class);
        $this->expectExceptionMessageMatches('/\[retry after] 60/');

        $this->transport->send($message, $envelope);
    }
}
