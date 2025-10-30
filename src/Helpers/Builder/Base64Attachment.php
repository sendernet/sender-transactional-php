<?php

namespace SenderNet\Helpers\Builder;

use InvalidArgumentException;

final class Base64Attachment extends Attachment
{
    private readonly string $base64;
    private readonly ?string $mimeType;

    public function __construct(string $filename, string $base64, ?string $mimeType = null)
    {
        if (empty($base64)) {
            throw new InvalidArgumentException('Base64 string cannot be empty');
        }

        if (base64_decode($base64, true) === false) {
            throw new InvalidArgumentException('Invalid base64 encoding');
        }

        parent::__construct($filename);
        $this->base64 = $base64;
        $this->mimeType = $mimeType;
    }

    public function getBase64(): string
    {
        return $this->base64;
    }

    public function getMimeType(): ?string
    {
        return $this->mimeType;
    }

    public function getValue(): string
    {
        return "base64://{$this->base64}";
    }
}
