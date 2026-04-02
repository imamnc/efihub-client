<p align="center">
        <a href="https://efihub.morefurniture.id">
                <img src="https://efihub.morefurniture.id/img/logo.png" alt="EFIHUB" width="180" />
        </a>
        <h1 align="center">EFIHUB PHP/Laravel Client</h1>
        <p align="center">
                <em>A modern SDK to integrate with the EFIHUB platform using the OAuth 2.0 Client Credentials flow.</em>
        </p>
        <p align="center">
                <a href="https://packagist.org/packages/imamnc/efihub-client"><img src="https://img.shields.io/packagist/v/imamnc/efihub-client.svg?logo=packagist" alt="packagist version" /></a>
                <img src="https://img.shields.io/badge/license-MIT-blue.svg" alt="license" />
                <a href="https://imamnc.com"><img src="https://img.shields.io/badge/author-Imam%20Nc-orange.svg" alt="author" /></a>
        </p>
</p>

## Introduction

EFIHUB client for Laravel/PHP that authenticates using OAuth 2.0 Client Credentials and exposes:

- A lightweight HTTP client (GET/POST/PUT/DELETE) with automatic token caching and retry on 401
- Storage module to upload files and get public URLs
- Websocket module to dispatch real-time events to channels
- WhatsApp module to manage agents (list, QR code, status, phone validation) and send messages (single/group) and file attachments
- SSO module to generate authorization URL and fetch user profile after callback

Designed for server-side apps only—do not expose your client secret to browsers.

---

## Table of Contents

- [Getting Started](#getting-started)
  - [Installation](#installation)
  - [Framework Setup](#framework-setup)
  - [Environment Variables](#environment-variables)
  - [Authentication](#authentication)
- [HTTP Client](#http-client)
  - [Basic Usage](#basic-usage)
  - [Dependency Injection](#dependency-injection)
- [Modules](#modules)
  - [Storage](#storage-module)
  - [WebSocket](#websocket-module)
  - [SSO](#sso-module)
  - [WhatsApp](#whatsapp-module)
- [License & Author](#license--author)

---

## Getting Started

### Installation

Install the package via Composer:

```bash
composer require imamnc/efihub-client
```

### Framework Setup

#### Laravel

Auto-discovery is enabled. Optionally publish the config file:

```bash
php artisan vendor:publish --provider="Efihub\EfihubServiceProvider" --tag=config
```

#### Lumen 7 (Illuminate 7.30.4)

Register the service provider in `bootstrap/app.php`:

```php
$app->register(Efihub\EfihubServiceProvider::class);
```

Enable and load the package config:

```php
$app->configure('efihub');
```

Copy the config file manually to `config/efihub.php` (Lumen does not support `vendor:publish`).

To use the Facade, enable facades and add an alias:

```php
$app->withFacades();

class_alias(Efihub\Facades\Efihub::class, 'Efihub');
```

### Environment Variables

Add the following to your `.env` file:

```env
EFIHUB_CLIENT_ID=your_client_id
EFIHUB_CLIENT_SECRET=your_client_secret
EFIHUB_TOKEN_URL=https://efihub.morefurniture.id/oauth/token
EFIHUB_API_URL=https://efihub.morefurniture.id/api
```

These map to `config/efihub.php` keys: `client_id`, `client_secret`, `token_url`, `api_base_url`.

### Authentication

The client uses the **OAuth 2.0 Client Credentials** flow automatically:

- An access token is fetched from `EFIHUB_TOKEN_URL` using your client credentials.
- The token is cached for ~55 minutes; on a `401` response, the cache is cleared and the request is retried once.
- No manual token management is needed—simply use the Facade or inject the client.

---

## HTTP Client

The base client handles authentication and exposes standard HTTP verbs. Use it directly for any EFIHUB API endpoint, or use the higher-level [Modules](#modules) for specific services.

### Basic Usage

```php
use Efihub\Facades\Efihub;

// GET with query params
$res = Efihub::get('/user', ['page' => 1]);

// POST JSON
$res = Efihub::post('/orders', ['sku' => 'ABC', 'qty' => 2]);

// PUT
$res = Efihub::put('/orders/123', ['qty' => 3]);

// DELETE
$res = Efihub::delete('/orders/123');

if ($res->successful()) {
    $data = $res->json();
}

// Multipart upload (form-data + file attachment)
$uploadRes = Efihub::postMultipart('/documents', ['type' => 'invoice'], [
    'file' => storage_path('app/invoices/jan.pdf'),
]);
if ($uploadRes->failed()) {
    // handle failure
}
```

### Dependency Injection

```php
use Efihub\EfihubClient;

class UserService
{
    /** @var EfihubClient */
    private $efihub;

    public function __construct(EfihubClient $efihub)
    {
        $this->efihub = $efihub;
    }

    public function list(): array
    {
        $res = $this->efihub->get('/user');
        return $res->successful() ? $res->json() : [];
    }
}
```

---

## Modules

All modules are accessed via the `Efihub` Facade (e.g. `Efihub::storage()`, `Efihub::whatsapp()`).

---

### Storage module

Upload files and manage them on the EFIHUB storage service.

#### Methods

`Efihub::storage()` returns `Efihub\Modules\StorageClient` with:

- `upload(mixed $file, string $path): string|false` – uploads a file; returns the public URL or `false` on failure.
- `exists(string $path): bool` – checks whether a file exists.
- `size(string $path): int|null` – returns file size in bytes, or `null` on failure.
- `delete(string $path): bool` – deletes a file; returns `true` on success.

`upload()` accepts a Laravel/Symfony `UploadedFile`, a string path, or raw file contents. End `$path` with `/` to let the server auto-generate the filename.

#### Usage

```php
use Illuminate\Http\Request;
use Efihub\Facades\Efihub;

class MediaController
{
    public function store(Request $request)
    {
        $request->validate([
            'file' => ['required', 'file', 'max:10240'], // 10 MB
        ]);

        $uploadedFile = $request->file('file'); // Illuminate\Http\UploadedFile

        // End path with '/' to auto-generate filename on the server
        $url = Efihub::storage()->upload($uploadedFile, 'uploads/'.date('Y/m/d').'/');

        if ($url === false) {
            return back()->withErrors('Upload failed');
        }

        return back()->with('url', $url);
    }
}
```

Other helpers:

```php
Efihub::storage()->exists('uploads/photo.jpg'); // bool
Efihub::storage()->size('uploads/photo.jpg');   // int|null (bytes)
Efihub::storage()->delete('uploads/photo.jpg'); // bool
```

#### Endpoints

- `POST /storage/upload`
- `GET /storage/url`
- `GET /storage/size`
- `GET /storage/exists`
- `DELETE /storage/delete`

---

### WebSocket module

Dispatch real-time events to channels from a server-side listener or job.

#### Methods

`Efihub::socket()` returns `Efihub\Modules\WebsocketClient` with:

- `dispatch(string $channel, string $event, array $data): bool` – broadcasts an event; returns `true` on success.

#### Usage

```php
use Efihub\Facades\Efihub;

$ok = Efihub::socket()->dispatch(
    'orders:updates',
    'OrderUpdated',
    ['order_id' => 123, 'status' => 'updated']
);

if (!$ok) {
    // handle failure (log, retry, etc.)
}
```

#### Endpoint

- `POST /websocket/dispatch` — payload: `{ channel, event, data }`

---

### SSO module

Centralized login flow: generate an authorization URL, redirect the user, then exchange the `redirect_token` for user data.

#### Methods

`Efihub::sso()` returns `Efihub\Modules\SSOClient` with:

- `login(): string|false` – returns the authorization URL or `false` on failure.
- `userData(string $redirectToken): array|false` – exchanges the token for user info; returns an array or `false` on failure.

#### Usage

**Step 1 — Redirect the user to EFIHUB login:**

```php
use Efihub\Facades\Efihub;

$authUrl = Efihub::sso()->login();
if ($authUrl === false) {
    // log error and abort
}

return redirect()->away($authUrl);
```

**Step 2 — Handle the callback and fetch user data:**

```php
$token = request('redirect_token');
$user = Efihub::sso()->userData($token);

if ($user === false) {
    // invalid token or request failed
} else {
    // $user['email'], $user['name'], ...
}
```

#### Endpoints

- `POST /sso/authorize`
- `GET /sso/user`

> Security enhancements like `state` / `nonce` can be layered externally; ensure you bind the session before redirecting.

---

### WhatsApp module

Manage WhatsApp agents and send messages (text or with attachments) to individual recipients or groups.

#### Methods

`Efihub::whatsapp()` returns `Efihub\Modules\WhatsappClient` with:

**Agent management**

| Method                                                | Returns        | Description                                                  |
| ----------------------------------------------------- | -------------- | ------------------------------------------------------------ |
| `agents()`                                            | `array`        | List all registered agents/sessions. Empty array on failure. |
| `agentQR(string $agentCode)`                          | `string\|null` | QR code as `image/png;base64`, or `null` on failure.         |
| `agentStatus(string $agentCode)`                      | `string\|null` | `'connected'` or `'disconnected'`, or `null` on failure.     |
| `checkPhoneNumber(string $agentCode, string $number)` | `bool`         | `true` if the number is a valid WhatsApp user.               |

**Messaging**

| Method                                                                                                                   | Returns | Description                                                  |
| ------------------------------------------------------------------------------------------------------------------------ | ------- | ------------------------------------------------------------ |
| `sendMessage(string $sender, string $to, string $message, ?string $ref_id, ?string $ref_url)`                            | `bool`  | Send a text message to a single recipient.                   |
| `sendGroupMessage(string $sender, string $to, string $message, ?string $ref_id, ?string $ref_url)`                       | `bool`  | Send a text message to a group.                              |
| `sendAttachment(string $sender, string $to, string $message, mixed $attachment, ?string $ref_id, ?string $ref_url)`      | `bool`  | Send a message with a file attachment to a single recipient. |
| `sendGroupAttachment(string $sender, string $to, string $message, mixed $attachment, ?string $ref_id, ?string $ref_url)` | `bool`  | Send a message with a file attachment to a group.            |

All methods return `true` on HTTP success (2xx), `false` otherwise.

#### Agent management

```php
use Efihub\Facades\Efihub;

// List all registered agents
$agents = Efihub::whatsapp()->agents();
// returns: [ ['code' => 'AGENT1', ...], ... ]

// Get QR code image (base64 PNG) to display for scanning
$qr = Efihub::whatsapp()->agentQR('AGENT1');
// returns: 'data:image/png;base64,...' or null

// Check connection status
$status = Efihub::whatsapp()->agentStatus('AGENT1');
// returns: 'connected' | 'disconnected' | null

// Validate a phone number before sending
$valid = Efihub::whatsapp()->checkPhoneNumber('AGENT1', '628109998877');
if (!$valid) {
    // number is not on WhatsApp
}
```

#### Sending messages

```php
use Efihub\Facades\Efihub;

// Send to a single recipient
$ok = Efihub::whatsapp()->sendMessage(
    sender: '+6281234567890',
    to: '+628109998877',
    message: 'Halo! Tes WhatsApp.'
);

// Send to a group
$ok = Efihub::whatsapp()->sendGroupMessage(
    sender: '+6281234567890',
    to: 'group-abc123', // group identifier
    message: 'Hello World!'
);

if (!$ok) {
    // log or retry
}
```

#### Attachments

```php
// Single file by path
$ok = Efihub::whatsapp()->sendAttachment(
    sender: '+6281234567890',
    to: '+628109998877',
    message: 'Berikut invoice kamu',
    attachment: storage_path('app/invoices/jan.pdf'),
);

// Raw contents with custom filename & MIME type
$ok = Efihub::whatsapp()->sendAttachment(
    sender: '+6281234567890',
    to: '+628109998877',
    message: 'Report CSV',
    attachment: [
        'contents' => file_get_contents(storage_path('app/tmp/report.csv')),
        'filename' => 'report.csv',
        'headers'  => ['Content-Type' => 'text/csv'],
    ],
);

// Multiple files to a group
$ok = Efihub::whatsapp()->sendGroupAttachment(
    sender: '+6281234567890',
    to: 'group-abc123',
    message: 'Semua dokumen',
    attachment: [
        storage_path('app/docs/a.pdf'),
        storage_path('app/docs/b.pdf'),
    ],
);
```

**Supported file spec formats:**

| Format                          | Example                                                                           |
| ------------------------------- | --------------------------------------------------------------------------------- |
| Path string                     | `'/path/to/file.pdf'`                                                             |
| Path array with custom name     | `['path' => '/path/to/file.pdf', 'filename' => 'custom.pdf', 'headers' => [...]]` |
| Raw contents                    | `['contents' => $binary, 'filename' => 'name.ext', 'headers' => [...]]`           |
| Multiple files (array of specs) | `['/path/a.jpg', '/path/b.jpg']`                                                  |

#### Reference metadata

All four send methods accept optional `$ref_id` and `$ref_url` parameters to correlate outbound messages with internal entities (orders, tickets, etc.):

```php
// Text message
Efihub::whatsapp()->sendMessage(
    sender: '+6281234567890',
    to: '+628109998877',
    message: 'Your order has been shipped.',
    ref_id: 'order-9988',
    ref_url: 'https://yourapp.com/orders/9988',
);

// Text message to a group
Efihub::whatsapp()->sendGroupMessage(
    sender: '+6281234567890',
    to: 'group-abc123',
    message: 'Bulk shipment processed.',
    ref_id: 'batch-42',
    ref_url: 'https://yourapp.com/batches/42',
);

// Attachment to a single recipient
Efihub::whatsapp()->sendAttachment(
    sender: '+6281234567890',
    to: '+628109998877',
    message: 'Berikut invoice kamu.',
    attachment: storage_path('app/invoices/jan.pdf'),
    ref_id: 'invoice-101',
    ref_url: 'https://yourapp.com/invoices/101',
);

// Attachment to a group
Efihub::whatsapp()->sendGroupAttachment(
    sender: '+6281234567890',
    to: 'group-abc123',
    message: 'Laporan bulanan.',
    attachment: storage_path('app/reports/jan.pdf'),
    ref_id: 'report-jan',
    ref_url: 'https://yourapp.com/reports/jan',
);
```

#### Error inspection

The helpers only return `bool`. For full error details, use the base HTTP client directly:

```php
$client = app(\Efihub\EfihubClient::class);
$response = $client->post('/whatsapp/send_message', [ /* payload */ ]);
if ($response->failed()) {
    logger()->error('WA send failed', [
        'status' => $response->status(),
        'body'   => $response->json(),
    ]);
}
```

#### Phone number normalization

All sending methods (`sendMessage`, `sendGroupMessage`, `sendAttachment`, `sendGroupAttachment`) automatically normalize phone numbers to the international format (`62xxxxxxxxxx` for Indonesia). You may pass numbers in any common format:

- `+628109998877` → `628109998877`
- `08109998877` → `628109998877`
- `628109998877` → `628109998877` (unchanged)

#### Endpoints

| Method                  | Endpoint                                         |
| ----------------------- | ------------------------------------------------ |
| `agents()`              | `GET /whatsapp/sessions`                         |
| `agentQR()`             | `GET /whatsapp/sessions/qrcode/{agentCode}`      |
| `agentStatus()`         | `GET /whatsapp/sessions/status/{agentCode}`      |
| `checkPhoneNumber()`    | `GET /whatsapp/user/exists/{agentCode}/{number}` |
| `sendMessage()`         | `POST /whatsapp/message`                         |
| `sendGroupMessage()`    | `POST /whatsapp/message/group`                   |
| `sendAttachment()`      | `POST /whatsapp/message/attachment`              |
| `sendGroupAttachment()` | `POST /whatsapp/message/group/attachment`        |

> Adjust paths if your EFIHUB deployment customizes routing.

---

## License & Author

MIT © Imam Nurcholis. See [LICENSE](LICENSE).

- Author: https://github.com/imamnc
- EFIHUB: https://efihub.morefurniture.id
