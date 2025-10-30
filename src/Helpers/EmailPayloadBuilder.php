<?php

namespace SenderNet\Helpers;

use SenderNet\Helpers\Builder\Attachment;
use SenderNet\Helpers\Builder\EmailParams;
use SenderNet\Helpers\Builder\Personalization;
use SenderNet\Helpers\Builder\Recipient;

class EmailPayloadBuilder
{
    public static function fromEmailParams(EmailParams $params): array
    {
        $recipients = GeneralHelpers::mapToArray($params->getRecipients(), Recipient::class);
        $primaryRecipient = ($firstRecipient = reset($recipients)) ? $firstRecipient : null;

        $payload = [
            'from' => self::buildEmailContact($params->getFrom(), $params->getFromName()),
            'reply_to' => self::buildEmailContact($params->getReplyTo(), $params->getReplyToName()),
            'to' => $primaryRecipient,
            'subject' => $params->getSubject(),
            'text' => $params->getText(),
            'html' => $params->getHtml(),
            'headers' => $params->getHeaders(),
            'variables' => $params->getVariables(),
            'attachments' => self::buildAttachmentsMap($params->getAttachments()),
        ];

        return self::removeNullValues($payload);
    }

    protected static function buildAttachmentsMap(array $attachments): ?array
    {
        if (empty($attachments)) {
            return null;
        }

        $attachmentsMap = [];
        foreach ($attachments as $attachment) {
            if ($attachment instanceof Attachment) {
                $attachmentsMap[$attachment->getFilename()] = $attachment->getValue();
            }
        }

        return empty($attachmentsMap) ? null : $attachmentsMap;
    }

    protected static function buildEmailContact(?string $email, ?string $name): ?array
    {
        if ($email === null) {
            return null;
        }

        $contact = [
            'email' => $email,
            'name' => $name,
        ];

        $filtered = self::removeNullValuesFromArray($contact);

        return $filtered === [] ? null : $filtered;
    }

    protected static function removeNullValues(array $payload): array
    {
        $filtered = [];

        foreach ($payload as $key => $value) {
            if (is_array($value)) {
                $value = self::removeNullValuesFromArray($value);
                if ($value === []) {
                    continue;
                }
                $filtered[$key] = $value;
                continue;
            }

            if ($value !== null) {
                $filtered[$key] = $value;
            }
        }

        return $filtered;
    }

    protected static function removeNullValuesFromArray(array $values): array
    {
        return array_filter($values, static fn ($item) => $item !== null);
    }
}
