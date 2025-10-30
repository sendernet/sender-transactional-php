# Sender.net PHP SDK

A modern PHP client for Sender.net transactional email API. The SDK provides a fluent builder for email payloads, consistent exception types, and first-class Laravel transport integration.

## Table of Contents

- [Requirements](#requirements)
- [Installation](#installation)
- [Quick Start](#quick-start)
- [Email Features](#email-features)
- [Error Handling](#error-handling)
- [Laravel Integration](#laravel-integration)
- [Testing](#testing)
- [License](#license)

## Requirements

- PHP 8.1 or newer
- PSR-18 HTTP client implementation (e.g. `php-http/guzzle7-adapter`)
- PSR-17 request & stream factories (e.g. `nyholm/psr7`)
- SenderNet API key

## Installation

Install the recommended HTTP client and factories:

```bash
composer require php-http/guzzle7-adapter nyholm/psr7
```

Install the SDK:

```bash
composer require sendernet/sender-transactional-php
```

## Quick Start

```php
use SenderNet\SenderNet;
use SenderNet\Helpers\Builder\EmailParams;
use SenderNet\Helpers\Builder\Recipient;

$sender = new SenderNet(['api_key' => getenv('SENDER_API_KEY')]);

$emailParams = (new EmailParams())
    ->setFrom('no-reply@example.com')
    ->setFromName('Example App')
    ->setRecipients([new Recipient('user@example.com', 'User')])
    ->setSubject('Welcome')
    ->setText('Thanks for signing up\!');

$response = $sender->email->send($emailParams);
```

Additional recipes are available in [`GUIDE.md`](GUIDE.md).

## Email Features

- **Single primary recipient** enforced by validation (`setRecipients()` must receive exactly one entry).
- **Content options** for HTML, text and custom headers.
- **Attachments** using `setAttachments()`.


## Error Handling

- All HTTP failures raise subclasses of `SenderNet\Exceptions\SenderNetRequestException` exposing the original PSR-7 request and response.
- Validation problems surface as `SenderNetValidationException`, which provides:
  - `getErrors()` for the raw payload array.
  - `getErrorMessages()` for formatted strings (e.g. `field: message`).
  - `getFirstError()` for the first human-readable issue.
- Rate limiting throws `SenderNetRateLimitException` including `Retry-After` metadata.

## Laravel Integration

The package provides a Laravel mail transport so you can send messages using the familiar `Mail` facade.

1. Install the SDK (auto-discovery registers `SenderServiceProvider`).
2. Run the installer for guided setup:

   ```bash
   php artisan sender:install
   ```

3. Configure environment variables:

   ```env
   SENDER_API_KEY=your_api_key
   MAIL_MAILER=sender
   ```



4. Use Laravel's mailing features as usual. Any `SenderNetRequestException` raised by the SDK is converted into `Symfony\Component\Mailer\Exception\TransportException` with the original message preserved.

## Testing

```bash
composer exec phpunit
```

## License

Released under the [MIT License](LICENSE).
