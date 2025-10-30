<?php

namespace SenderNet\Helpers\Builder;

use Assert\Assertion;
use SenderNet\Contracts\Arrayable;
use SenderNet\Exceptions\SenderNetAssertException;
use SenderNet\Helpers\GeneralHelpers;

class Header implements Arrayable, \JsonSerializable
{
    protected string $name;
    protected string $value;

    /**
     * @throws SenderNetAssertException
     */
    public function __construct(string $name, string $value)
    {
        $this->setName($name);
        $this->setValue($value);
    }

    /**
     * @throws SenderNetAssertException
     */
    public function setName(string $name): void
    {
        GeneralHelpers::assert(static function () use ($name) {
            Assertion::notEmpty($name);
            Assertion::string($name);
        });

        $this->name = $name;
    }

    /**
     * @throws SenderNetAssertException
     */
    public function setValue(string $value): void
    {
        GeneralHelpers::assert(static function () use ($value) {
            Assertion::notEmpty($value);
            Assertion::string($value);
        });

        $this->value = $value;
    }

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'value' => $this->value,
        ];
    }

    #[\ReturnTypeWillChange]
    public function jsonSerialize()
    {
        return $this->toArray();
    }
}
