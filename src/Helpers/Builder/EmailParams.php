<?php

namespace SenderNet\Helpers\Builder;

class EmailParams
{
    protected ?string $from = null;
    protected ?string $from_name = null;
    protected ?string $reply_to = null;
    protected ?string $reply_to_name = null;
    protected array $recipients = [];
    protected ?string $subject = null;
    protected ?string $html = null;
    protected ?string $text = null;
    protected array $headers = [];
    protected array $variables = [];
    protected array $attachments = [];

    public function getFrom(): ?string
    {
        return $this->from;
    }

    public function setFrom(string $from): self
    {
        $this->from = $from;

        return $this;
    }

    public function getFromName(): ?string
    {
        return $this->from_name;
    }

    public function setFromName(string $from_name): self
    {
        $this->from_name = $from_name;

        return $this;
    }

    public function getReplyTo(): ?string
    {
        return $this->reply_to;
    }

    public function setReplyTo(?string $reply_to): self
    {
        $this->reply_to = $reply_to;

        return $this;
    }

    public function getReplyToName(): ?string
    {
        return $this->reply_to_name;
    }

    public function setReplyToName(?string $reply_to_name): self
    {
        $this->reply_to_name = $reply_to_name;

        return $this;
    }

    public function getRecipients(): array
    {
        return $this->recipients;
    }

    public function setRecipients(array $recipients): self
    {
        $this->recipients = $recipients;

        return $this;
    }

    public function getSubject(): ?string
    {
        return $this->subject;
    }

    public function setSubject(string $subject): self
    {
        $this->subject = $subject;

        return $this;
    }

    public function getHtml(): ?string
    {
        return $this->html;
    }

    public function setHtml(?string $html): self
    {
        $this->html = $html;

        return $this;
    }

    public function getText(): ?string
    {
        return $this->text;
    }

    public function setText(?string $text): self
    {
        $this->text = $text;

        return $this;
    }

    public function getHeaders(): array
    {
        return $this->headers;
    }

    public function setHeaders(array $headers): self
    {
        $this->headers = $headers;

        return $this;
    }

    public function getVariables(): array
    {
        return $this->variables;
    }

    public function setVariables(array $variables): self
    {
        $this->variables = $variables;

        return $this;
    }

    public function getAttachments(): array
    {
        return $this->attachments;
    }

    public function setAttachments(array $attachments): self
    {
        $this->attachments = $attachments;

        return $this;
    }

    public function hasUrlAttachments(): bool
    {
        if (empty($this->attachments)) {
            return false;
        }

        foreach ($this->attachments as $attachment) {
            if ($attachment instanceof UrlAttachment) {
                return true;
            }
        }

        return false;
    }

    public function hasBase64Attachments(): bool
    {
        if (empty($this->attachments)) {
            return false;
        }

        foreach ($this->attachments as $attachment) {
            if ($attachment instanceof Base64Attachment) {
                return true;
            }
        }

        return false;
    }
}
