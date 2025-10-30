<?php

namespace SenderNet\Helpers\Builder;

use InvalidArgumentException;

abstract class Attachment
{
    protected readonly string $filename;

    protected function __construct(string $filename)
    {
        if (empty($filename)) {
            throw new InvalidArgumentException('Filename cannot be empty');
        }

        $this->filename = $filename;
    }

    public function getFilename(): string
    {
        return $this->filename;
    }

    abstract public function getValue(): string;
}
