<?php

namespace SenderNet\Helpers\Builder;

use Assert\Assertion;
use SenderNet\Contracts\Arrayable;
use SenderNet\Exceptions\SenderNetAssertException;
use SenderNet\Helpers\GeneralHelpers;

class Recipient implements Arrayable, \JsonSerializable
{
    protected ?string $name;
    protected string $email;

    /**
     * @throws SenderNetAssertException
     */
    public function __construct(string $email, ?string $name)
    {
        $this->setEmail($email);
        $this->setName($name);
    }

    public function setName(?string $name): void
    {
        $this->name = $name;
    }

    /**
     * @throws SenderNetAssertException
     */
    public function setEmail(string $email): void
    {
        GeneralHelpers::assert(static function () use ($email) {
            Assertion::email($email);
        });

        $this->email = $email;
    }

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'email' => $this->email,
        ];
    }

    #[\ReturnTypeWillChange]
    public function jsonSerialize()
    {
        return $this->toArray();
    }
}
