<?php

namespace SenderNet\Laravel;

use SenderNet\Exceptions\SenderNetRequestException;
use SenderNet\Helpers\Builder\Attachment;
use SenderNet\Helpers\Builder\UrlAttachment;
use SenderNet\Helpers\Builder\Base64Attachment;
use SenderNet\Helpers\Builder\EmailParams;
use SenderNet\Helpers\Builder\Recipient;
use SenderNet\SenderNet;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\Mailer\Envelope;
use Symfony\Component\Mailer\Exception\TransportException;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\Header\MetadataHeader;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\TransportInterface;
use Symfony\Component\Mime\RawMessage;

class SenderTransport implements TransportInterface
{
    protected SenderNet $sender;

    public function __construct(SenderNet $sender)
    {
        $this->sender = $sender;
    }

    /**
     * @throws \Assert\AssertionFailedException
     * @throws \JsonException
     * @throws \Psr\Http\Client\ClientExceptionInterface
     * @throws TransportExceptionInterface
     * @throws \SenderNet\Exceptions\SenderNetAssertException
     */
    public function send(RawMessage $message, ?Envelope $envelope = null): ?SentMessage
    {
        try{
            ['email' => $fromEmail, 'name' => $fromName] = $this->getFrom($message);
            ['email' => $replyToEmail, 'name' => $replyToName] = $this->getReplyTo($message);

            $text = $message->getTextBody();
            $html = $message->getHtmlBody();

            $to = $this->getRecipients('to', $message);

            $subject = $message->getSubject();

            $attachments = $this->getAttachments($message);
            $variables = $this->getVariables($message);

            $emailParams = app(EmailParams::class)
                ->setFrom($fromEmail)
                ->setFromName($fromName)
                ->setRecipients($to)
                ->setSubject($subject)
                ->setHtml($html)
                ->setText($text);

            if (!empty($replyToEmail)) {
                $emailParams->setReplyTo($replyToEmail)->setReplyToName(strval($replyToName));
            }

            if (!empty($attachments)) {
                $emailParams->setAttachments($attachments);
            }

            if (!empty($variables)) {
                $emailParams->setVariables($variables);
            }

            $response = $this->sender->email->send($emailParams);

            /** @var ResponseInterface $respInterface */
            $respInterface = $response['response'];

            if ($messageId = $respInterface->getHeaderLine('X-Message-Id')) {
                $message->getHeaders()?->addTextHeader('X-SenderNet-Message-Id', $messageId);
            }

            if ($body = $respInterface->getBody()->getContents()) {
                $message->getHeaders()?->addTextHeader('X-SenderNet-Body', $body);
            }

            return new SentMessage($message, $envelope);
        }catch (SenderNetRequestException $exception){
            throw new TransportException($exception->getMessage(), $exception->getCode());
        }
    }

    /**
     * @throws TransportExceptionInterface
     */
    protected function getFrom(RawMessage $message): array
    {
        $from = $message->getFrom();

        if (count($from) === 0) {
            throw new TransportException('FROM address is required. Please set exactly one FROM address.');
        }

        if (count($from) > 1) {
            throw new TransportException(
                sprintf(
                    'Multiple FROM addresses are not supported. Found %d addresses, but only one is allowed.',
                    count($from)
                )
            );
        }

        return ['name' => $from[0]->getName(), 'email' => $from[0]->getAddress()];
    }

    /**
     * @throws TransportExceptionInterface
     */
    protected function getReplyTo(RawMessage $message): array
    {
        $replyTo = $message->getReplyTo();

        if (count($replyTo) > 1) {
            throw new TransportException(
                sprintf(
                    'Multiple REPLY-TO addresses are not supported. Found %d addresses, but only one is allowed.',
                    count($replyTo)
                )
            );
        }

        if (count($replyTo) === 1) {
            return ['name' => $replyTo[0]->getName(), 'email' => $replyTo[0]->getAddress()];
        }

        return ['email' => '', 'name' => ''];
    }

    /**
     * @throws \SenderNet\Exceptions\SenderNetAssertException
     */
    protected function getRecipients(string $type, RawMessage $message): array
    {
        $recipients = [];

        if ($addresses = $message->{'get'.ucfirst($type)}()) {
            foreach ($addresses as $address) {
                $recipients[] = new Recipient($address->getAddress(), $address->getName());
            }
        }

        return $recipients;
    }

    protected function getAttachments(RawMessage $message): array
    {
        $attachments = [];

        foreach ($message->getAttachments() as $attachment) {
            $filename = $this->extractFilename($attachment);
            $url = $this->tryExtractUrl($attachment);

            if ($url !== null) {
                $attachments[] = new UrlAttachment($filename, $url);
            } else {
                $attachments[] = $this->convertToBase64Attachment($attachment, $filename);
            }
        }

        return $attachments;
    }

    protected function convertToBase64Attachment($attachment, string $filename): Base64Attachment
    {
        $content = $attachment->getBody();
        $base64 = base64_encode($content);
        $mimeType = $attachment->getContentType();

        return new Base64Attachment($filename, $base64, $mimeType);
    }

    protected function extractFilename($attachment): string
    {
        $filename = $attachment->getPreparedHeaders()
            ->get('content-disposition')
            ?->getParameter('filename');

        return $filename ?: ($attachment->getFilename() ?? 'attachment');
    }

    protected function tryExtractUrl($attachment): ?string
    {
        try {
            $body = $this->getAttachmentBody($attachment);

            if ($body instanceof \Symfony\Component\Mime\Part\File) {
                $path = $body->getPath();
                return filter_var($path, FILTER_VALIDATE_URL) ? $path : null;
            }

            if (is_string($body) && filter_var($body, FILTER_VALIDATE_URL)) {
                return $body;
            }

            return null;
        } catch (\Exception $e) {
            return null;
        }
    }

    protected function getAttachmentBody($attachment)
    {
        $reflectionClass = new \ReflectionClass($attachment);

        while ($reflectionClass) {
            try {
                $bodyProperty = $reflectionClass->getProperty('body');
                $bodyProperty->setAccessible(true);

                return $bodyProperty->getValue($attachment);
            } catch (\ReflectionException $e) {
                $reflectionClass = $reflectionClass->getParentClass();
            }
        }

        return null;
    }

    protected function getVariables(RawMessage $message): array
    {
        $variables = [];
        $headers = $message->getHeaders();

        foreach ($headers->all() as $header) {
            if ($header instanceof MetadataHeader) {
                $variables[$header->getKey()] = $header->getBodyAsString();
            }
        }

        return $variables;
    }

    public function __toString(): string
    {
        return 'sender';
    }
}
