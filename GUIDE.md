# SenderNet PHP SDK Guide

This guide complements the main README by showing concrete payload builders, validation helpers, attachment handling, and exception management.

## Table of Contents

- [Basic Email](#basic-email)
- [Recipients](#recipients)
- [Content Types](#content-types)
- [Attachments](#attachments)
- [Custom Headers](#custom-headers)
- [Tracking & List Management](#tracking)
- [Exception Handling](#exceptions)
- [Advanced Configuration](#advanced)

---

<a name="basic-email"></a>
## Basic Email

```php
use SenderNet\SenderNet;
use SenderNet\Helpers\Builder\EmailParams;
use SenderNet\Helpers\Builder\Recipient;

$sender = new SenderNet(['api_key' => 'your-api-key']);

$emailParams = (new EmailParams())
    ->setFrom('hello@sender.net')
    ->setFromName('Sender Team')
    ->setRecipients([new Recipient('user@example.com', 'User Name')])
    ->setSubject('Welcome to Sender')
    ->setText('Plain text content')
    ->setHtml('<h1>Welcome!</h1><p>HTML content</p>');

$response = $sender->email->send($emailParams);
```

---

<a name="recipients"></a>
## Recipients

### Primary Recipient (Required)

Exactly one primary recipient must be provided:

```php
use SenderNet\Helpers\Builder\Recipient;

$emailParams = (new EmailParams())
    ->setFrom('team@sender.net')
    ->setRecipients([
        new Recipient('user@example.com', 'User Name')
    ])
    ->setSubject('Your Report')
    ->setText('Content');
```

### Reply-To Address

```php
$emailParams = (new EmailParams())
    ->setFrom('noreply@sender.net')
    ->setFromName('Notifications')
    ->setReplyTo('support@sender.net')
    ->setReplyToName('Support Team')
    ->setRecipients([new Recipient('user@example.com')])
    ->setSubject('Your Ticket Update')
    ->setText('Content');
```

---

<a name="content-types"></a>
## Content Types

### Plain Text

```php
$emailParams = (new EmailParams())
    ->setFrom('news@sender.net')
    ->setRecipients([new Recipient('user@example.com')])
    ->setSubject('Newsletter')
    ->setText('This is plain text content.');
```

### HTML Content

```php
$emailParams = (new EmailParams())
    ->setFrom('news@sender.net')
    ->setRecipients([new Recipient('user@example.com')])
    ->setSubject('Newsletter')
    ->setHtml('<h1>Newsletter</h1><p>HTML content</p>');
```

### Both Text and HTML

```php
$emailParams = (new EmailParams())
    ->setFrom('news@sender.net')
    ->setRecipients([new Recipient('user@example.com')])
    ->setSubject('Newsletter')
    ->setText('Plain text version')
    ->setHtml('<h1>Newsletter</h1><p>HTML version</p>');
```

### Template ID

```php
$emailParams = (new EmailParams())
    ->setFrom('welcome@sender.net')
    ->setRecipients([new Recipient('user@example.com')])
    ->setSubject('Welcome')
    ->setTemplateId('template_12345');
```

---

<a name="attachments"></a>
## Attachments

### URL-Based Attachments

Attach files from URLs without downloading them:

```php
use SenderNet\Helpers\Builder\UrlAttachment;

$emailParams = (new EmailParams())
    ->setFrom('reports@sender.net')
    ->setRecipients([new Recipient('user@example.com')])
    ->setSubject('Monthly Report')
    ->setHtml('<h1>Report</h1><p>See attachments</p>')
    ->setAttachments([
        new UrlAttachment('report.pdf', 'https://example.com/files/report.pdf'),
        new UrlAttachment('chart.png', 'https://example.com/files/chart.png'),
    ]);

$response = $sender->email->send($emailParams);
```

### Base64 Attachments

Attach files as base64 encoded strings:

```php
use SenderNet\Helpers\Builder\Base64Attachment;

$invoiceContent = file_get_contents('/path/to/invoice.pdf');
$receiptContent = file_get_contents('/path/to/receipt.pdf');

$emailParams = (new EmailParams())
    ->setFrom('documents@sender.net')
    ->setRecipients([new Recipient('user@example.com')])
    ->setSubject('Your Documents')
    ->setHtml('<h1>Documents</h1>')
    ->setAttachments([
        new Base64Attachment('invoice.pdf', base64_encode($invoiceContent), 'application/pdf'),
        new Base64Attachment('receipt.pdf', base64_encode($receiptContent), 'application/pdf'),
    ]);

$response = $sender->email->send($emailParams);
```

**Note:** The optional `mimeType` parameter (third argument) will prefix the base64 string with a data URI format (`data:application/pdf;base64,...`). Omit it to send raw base64.

### Mixed Attachments

You can combine URL and base64 attachments:

```php
use SenderNet\Helpers\Builder\UrlAttachment;
use SenderNet\Helpers\Builder\Base64Attachment;

$localContent = file_get_contents('/path/to/local.pdf');

$emailParams = (new EmailParams())
    ->setFrom('documents@sender.net')
    ->setRecipients([new Recipient('user@example.com')])
    ->setSubject('Mixed Attachments')
    ->setHtml('<h1>Documents</h1>')
    ->setAttachments([
        new UrlAttachment('logo.png', 'https://cdn.example.com/logo.png'),
        new Base64Attachment('document.pdf', base64_encode($localContent), 'application/pdf'),
    ]);

$response = $sender->email->send($emailParams);
```

---

<a name="custom-headers"></a>
## Custom Headers

```php
use SenderNet\Helpers\Builder\Header;

$emailParams = (new EmailParams())
    ->setFrom('campaign@sender.net')
    ->setRecipients([new Recipient('user@example.com')])
    ->setSubject('Campaign Email')
    ->setHtml('<h1>Special Offer</h1>')
    ->setHeaders([
        new Header('X-Campaign-ID', 'summer-2025'),
        new Header('X-User-Segment', 'premium'),
        new Header('X-Priority', 'high'),
    ]);
```

---

<a name="tracking"></a>
## Tracking & List Management

### Email Tracking

```php
$emailParams = (new EmailParams())
    ->setFrom('marketing@sender.net')
    ->setRecipients([new Recipient('user@example.com')])
    ->setSubject('Newsletter')
    ->setHtml('<h1>Newsletter</h1>')
    ->setTrackOpens(true)
    ->setTrackClicks(true)
    ->setTrackContent(true);
```

### List Management

```php
$emailParams = (new EmailParams())
    ->setFrom('newsletter@sender.net')
    ->setRecipients([new Recipient('user@example.com')])
    ->setSubject('Monthly Newsletter')
    ->setHtml('<h1>Newsletter</h1>')
    ->setListUnsubscribe('https://example.com/unsubscribe?id=12345')
    ->setPrecedenceBulkHeader(true);
```

### Scheduled Send

```php
$emailParams = (new EmailParams())
    ->setFrom('scheduled@sender.net')
    ->setRecipients([new Recipient('user@example.com')])
    ->setSubject('Scheduled Email')
    ->setText('This will be sent later')
    ->setSendAt('2025-12-01 10:00:00');
```

---

<a name="exceptions"></a>
## Exception Handling

### Validation Errors

```php
use SenderNet\Exceptions\SenderNetValidationException;

try {
    $emailParams = (new EmailParams())
        ->setFrom('test@sender.net')
        ->setRecipients([]) // Invalid: no recipient
        ->setSubject('Test')
        ->setText('Content');

    $sender->email->send($emailParams);
} catch (SenderNetValidationException $e) {
    // Get first error message
    echo $e->getFirstError();
    // Output: "recipients: This field is required"

    // Get all error messages
    $messages = $e->getErrorMessages();
    foreach ($messages as $message) {
        echo $message . "\n";
    }

    // Get raw error array
    $errors = $e->getErrors();
    // ['recipients' => ['This field is required']]

    // Get HTTP status code
    echo $e->getStatusCode(); // 422

    // Get response body
    echo $e->getBody();
}
```

### Rate Limiting

```php
use SenderNet\Exceptions\SenderNetRateLimitException;

try {
    $sender->email->send($emailParams);
} catch (SenderNetRateLimitException $e) {
    // Get Retry-After header value
    $retryAfter = $e->getHeaders()['Retry-After'][0] ?? null;

    echo "Rate limited. Retry after: " . $retryAfter;
    echo "Status: " . $e->getStatusCode(); // 429
}
```

### General Request Errors

```php
use SenderNet\Exceptions\SenderNetRequestException;
use SenderNet\Exceptions\SenderNetHttpException;

try {
    $sender->email->send($emailParams);
} catch (SenderNetHttpException $e) {
    // HTTP layer errors (network issues, timeouts)
    echo "HTTP Error: " . $e->getMessage();
} catch (SenderNetRequestException $e) {
    // API request errors (4xx, 5xx responses)
    echo "API Error: " . $e->getMessage();
    echo "Status Code: " . $e->getStatusCode();

    // Access PSR-7 objects
    $request = $e->getRequest();
    $response = $e->getResponse();
}
```

### Exception Hierarchy

```
SenderNetException (base)
  └── SenderNetRequestException
       ├── SenderNetValidationException (422)
       ├── SenderNetRateLimitException (429)
       └── SenderNetHttpException (network/transport)
```

### Catch-All Error Handling

```php
use SenderNet\Exceptions\SenderNetException;

try {
    $sender->email->send($emailParams);
} catch (SenderNetException $e) {
    // Catches all SDK exceptions
    error_log('Email send failed: ' . $e->getMessage());

    // Check exception type for specific handling
    if ($e instanceof \SenderNet\Exceptions\SenderNetValidationException) {
        // Handle validation errors
    } elseif ($e instanceof \SenderNet\Exceptions\SenderNetRateLimitException) {
        // Handle rate limiting
    }
}
```

---

<a name="advanced"></a>
## Advanced Configuration

### Custom API Endpoint

```php
$sender = new SenderNet([
    'api_key' => 'your-api-key',
    'host' => 'custom.api.sender.net',
    'protocol' => 'https',
    'api_path' => 'v2',
    'timeout' => 30,
]);
```

### Environment-Based API Key

```php
// Reads from SENDER_API_KEY environment variable
$sender = new SenderNet();

// Or explicit configuration
$sender = new SenderNet([
    'api_key' => getenv('SENDER_API_KEY')
]);
```

### Debug Mode

```php
$sender = new SenderNet([
    'api_key' => 'your-api-key',
    'debug' => true, // Enables debug output
]);
```

### Custom HTTP Client

Provide your own PSR-18 HTTP client:

```php
use SenderNet\Common\HttpLayer;

$httpLayer = new HttpLayer([
    'api_key' => 'your-api-key',
    // ... other options
]);

$sender = new SenderNet([], $httpLayer);
```

---

## Complete Example

```php
<?php

require 'vendor/autoload.php';

use SenderNet\SenderNet;
use SenderNet\Helpers\Builder\EmailParams;
use SenderNet\Helpers\Builder\Recipient;
use SenderNet\Helpers\Builder\Attachment;
use SenderNet\Helpers\Builder\Header;
use SenderNet\Exceptions\SenderNetValidationException;
use SenderNet\Exceptions\SenderNetRateLimitException;
use SenderNet\Exceptions\SenderNetRequestException;

$sender = new SenderNet(['api_key' => getenv('SENDER_API_KEY')]);

try {
    $emailParams = (new EmailParams())
        ->setFrom('hello@sender.net')
        ->setFromName('Sender Team')
        ->setReplyTo('support@sender.net')
        ->setReplyToName('Support')
        ->setRecipients([new Recipient('user@example.com', 'User Name')])
        ->setSubject('Complete Example')
        ->setText('Plain text version')
        ->setHtml('<h1>Complete Example</h1><p>With all features</p>')
        ->setAttachments([
            Attachment::fromUrl('logo.png', 'https://cdn.example.com/logo.png'),
        ])
        ->setHeaders([
            new Header('X-Campaign-ID', 'example-123'),
        ])
        ->setTrackOpens(true)
        ->setTrackClicks(true);

    $response = $sender->email->send($emailParams);

    echo "Email sent successfully!\n";
} catch (SenderNetValidationException $e) {
    echo "Validation Error: " . $e->getFirstError() . "\n";
} catch (SenderNetRateLimitException $e) {
    echo "Rate Limited: " . $e->getMessage() . "\n";
} catch (SenderNetRequestException $e) {
    echo "Request Error: " . $e->getMessage() . "\n";
}
```

---

## Laravel Integration

For Laravel-specific usage including Mail facade integration, see [README.md § Laravel Integration](README.md#laravel-integration).

**Note:** When using Laravel Mail, `SenderNetRequestException` converts to `Symfony\Component\Mailer\Exception\TransportException`.
