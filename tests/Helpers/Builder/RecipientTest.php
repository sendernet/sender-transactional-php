<?php

namespace SenderNet\Tests\Helpers\Builder;

use SenderNet\Helpers\Arr;
use SenderNet\Exceptions\SenderNetAssertException;
use SenderNet\Helpers\Builder\Recipient;
use SenderNet\Tests\TestCase;

class RecipientTest extends TestCase
{
    public function test_properly_sets_recipient_params(): void
    {
        $recipient = (new Recipient('email@example.com', 'Recipient'))->toArray();

        self::assertEquals('email@example.com', Arr::get($recipient, 'email'));
        self::assertEquals('Recipient', Arr::get($recipient, 'name'));
    }

    public function test_recipient_validates_email(): void
    {
        $this->expectException(SenderNetAssertException::class);

        (new Recipient('emailexample.com', 'Recipient'));
    }
}
