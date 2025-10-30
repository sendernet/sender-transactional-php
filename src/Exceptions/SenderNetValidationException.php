<?php

namespace SenderNet\Exceptions;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

class SenderNetValidationException extends SenderNetRequestException
{
    protected string $body;
    protected array $headers;
    protected int $statusCode;
    protected array $errors;

    public function __construct(
        RequestInterface $request,
        ResponseInterface $response
    ) {
        $stream = $response->getBody();
        $this->body = (string) $stream;
        if ($stream->isSeekable()) {
            $stream->rewind();
        }

        $this->headers = $response->getHeaders();
        $this->statusCode = $response->getStatusCode();

        $payload = $this->decodePayload($this->body);
        $this->errors = $payload['errors'] ?? [];

        parent::__construct(
            $request,
            $response,
            $payload['message'] ?? 'Validation Error'
        );
    }

    public function getBody(): string
    {
        return $this->body;
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function getErrors(): array
    {
        return $this->errors;
    }

    public function getHeaders(): array
    {
        return $this->headers;
    }

    public function getErrorMessages(): array
    {
        $messages = [];

        foreach ($this->errors as $field => $entries) {
            if (is_array($entries)) {
                foreach ($entries as $entry) {
                    if (!is_string($entry) || $entry === '') {
                        continue;
                    }

                    $messages[] = $this->formatErrorMessage($field, $entry);
                }

                continue;
            }

            if (is_string($entries) && $entries !== '') {
                $messages[] = $this->formatErrorMessage($field, $entries);
            }
        }

        return $messages;
    }

    public function getFirstError(): ?string
    {
        $messages = $this->getErrorMessages();

        return $messages[0] ?? null;
    }

    protected function decodePayload(string $body): array
    {
        $decoded = json_decode($body, true);

        return is_array($decoded) ? $decoded : [];
    }

    protected function formatErrorMessage(mixed $field, string $message): string
    {
        return is_string($field) && $field !== ''
            ? sprintf('%s: %s', $field, $message)
            : $message;
    }
}
