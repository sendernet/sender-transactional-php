<?php

namespace SenderNet\Helpers\Builder;

use InvalidArgumentException;

final class UrlAttachment extends Attachment
{
    private readonly string $url;

    public function __construct(string $filename, string $url)
    {
        if (empty($url)) {
            throw new InvalidArgumentException('URL cannot be empty');
        }

        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            throw new InvalidArgumentException("Invalid URL provided: {$url}");
        }

        parent::__construct($filename);
        $this->url = $url;
    }

    public function getUrl(): string
    {
        return $this->url;
    }

    public function getValue(): string
    {
        return $this->url;
    }
}
