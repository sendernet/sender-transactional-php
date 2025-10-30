<?php

namespace SenderNet\Helpers;

use Assert\Assertion;
use Assert\AssertionFailedException;
use SenderNet\Exceptions\SenderNetAssertException;
use SenderNet\Helpers\Builder\EmailParams;
use SenderNet\Helpers\Builder\SmsParams;

class GeneralHelpers
{
    /**
     * @throws SenderNetAssertException
     * @noinspection PhpRedundantCatchClauseInspection
     */
    public static function assert(callable $assertions): void
    {
        try {
            $assertions();
        } catch (AssertionFailedException $e) {
            throw new SenderNetAssertException($e->getMessage());
        }
    }

    public static function validateEmailParams(EmailParams $params): void
    {
        self::assert(fn () => Assertion::notEmpty(array_filter([
            $params->getText(),
            $params->getHtml(),
        ], static fn ($value) => $value !== null), 'One of html or text must be supplied'));

        $recipients = $params->getRecipients();

        self::assert(
            fn () => Assertion::count($recipients, 1, 'Exactly one primary recipient is required')
        );

        self::assert(
            fn () => Assertion::email($params->getFrom()) &&
                Assertion::minLength($params->getFromName(), 1) &&
                Assertion::minLength($params->getSubject(), 1)
        );
    }

    public static function validateSmsParams(SmsParams $params): void
    {
        self::assert(fn () => Assertion::notEmpty($params->getFrom(), 'From phone number is required'));
        self::assert(fn () => Assertion::startsWith($params->getFrom(), '+', 'From phone number must start with +'));
        self::assert(fn () => Assertion::notEmpty($params->getTo(), 'At least one recipient is required'));
        foreach ($params->getTo() as $recipient) {
            self::assert(fn () => Assertion::startsWith($recipient, '+', 'Recipient phone number must start with +'));
        }
        self::assert(fn () => Assertion::minLength($params->getText(), 1, 'Text cannot be empty'));
    }

    public static function mapToArray(array $data, string $object): array
    {
        return array_map(
            fn ($v) => is_object($v) && is_a($v, $object) ? $v->toArray() : $v,
            $data
        );
    }
}
