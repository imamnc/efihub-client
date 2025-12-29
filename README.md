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
- WhatsApp module to send messages (single/group) and file attachments
- SSO module to generate authorization URL and fetch user profile after callback
- Multipart form-data helper (`postMultipart`) for uploading documents or media

Designed for server-side apps only—do not expose your client secret to browsers.

## Installation

1. Install the package

```bash
composer require imamnc/efihub-client
```

2. Publish config (Laravel only; optional; auto-discovery is enabled)

```bash
php artisan vendor:publish --provider="Efihub\EfihubServiceProvider" --tag=config
```

### Lumen 7 (Illuminate 7.30.4)

- Register the service provider in `bootstrap/app.php`:

```php
$app->register(Efihub\EfihubServiceProvider::class);
```

- Ensure config is enabled and load the package config:

```php
$app->configure('efihub');
```

- Copy the config file manually to `config/efihub.php` (Lumen does not support `vendor:publish`).

- If you want to use the Facade, enable facades in Lumen and add an alias:

```php
$app->withFacades();

class_alias(Efihub\Facades\Efihub::class, 'Efihub');
```

3. Configure environment

```env
EFIHUB_CLIENT_ID=your_client_id
EFIHUB_CLIENT_SECRET=your_client_secret
EFIHUB_TOKEN_URL=https://efihub.morefurniture.id/oauth/token
EFIHUB_API_URL=https://efihub.morefurniture.id/api
```

## Authentication

- Uses OAuth 2.0 Client Credentials to obtain an access token from `EFIHUB_TOKEN_URL`
- Token is cached ~55 minutes; on 401, the client clears the cache and retries once
- Config file: `config/efihub.php` (keys: client_id, client_secret, token_url, api_base_url)

## Http client module

Use the Facade for simple calls or inject `Efihub\EfihubClient`.

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

Dependency Injection example:

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

## Storage module

Common Laravel use case: upload an `UploadedFile` and get its public URL.

```php
use Illuminate\Http\Request;
use Efihub\Facades\Efihub;

class MediaController
{
    public function store(Request $request)
    {
        $request->validate([
            'file' => ['required', 'file', 'max:10240'], // 10MB
        ]);

        $uploadedFile = $request->file('file'); // Illuminate\Http\UploadedFile

        // Upload to a folder; end with '/' to auto-generate a filename on server
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

Notes:

- upload() accepts Laravel/Symfony UploadedFile, string path, or raw contents
- Endpoints used: GET /storage/url|size|exists, POST /storage/upload, DELETE /storage/delete

## Websocket module

Dispatch real-time events to channels (e.g. from a listener or job):

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

Endpoint used: POST `/api/websocket/dispatch` with JSON `{ channel, event, data }`.

## SSO module

Centralized login flow: generate authorization URL, redirect user, then exchange `redirect_token` for user data.

### Methods

`Efihub::sso()` returns `Efihub\Modules\SSOClient` with:

- `login(): string|false` – returns authorization URL or `false`.
- `userData(string $redirectToken): array|false` – returns user info array or `false`.

### Generate authorization URL

```php
use Efihub\Facades\Efihub;

$authUrl = Efihub::sso()->login();
if ($authUrl === false) {
    // log error
}
// return redirect()->away($authUrl);
```

### Handle callback

Assuming your callback route receives `redirect_token`:

```php
$token = request('redirect_token');
$user = Efihub::sso()->userData($token);

if ($user === false) {
    // invalid token or request failed
} else {
    // $user['email'], $user['name'], ...
}
```

### Endpoints

- `POST /sso/authorize`
- `GET /sso/user`

Note: Security enhancements like `state` / `nonce` can be layered externally; ensure you bind the session before redirecting.

## WhatsApp module

Send WhatsApp messages (single recipient or group) with optional reference metadata and file attachments.

### Methods

`Efihub::whatsapp()` returns an instance of `Efihub\Modules\WhatsappClient` exposing:

- `sendMessage(string $sender, string $to, string $message, ?string $ref_id = null, ?string $ref_url = null): bool`
- `sendGroupMessage(string $sender, string $to, string $message, ?string $ref_id = null, ?string $ref_url = null): bool`
- `sendAttachment(string $sender, string $to, string $message, mixed $attachment): bool`
- `sendGroupAttachment(string $sender, string $to, string $message, mixed $attachment): bool`

Each returns `true` on HTTP success (2xx), otherwise `false`.

### Simple text message

```php
use Efihub\Facades\Efihub;

$ok = Efihub::whatsapp()->sendMessage(
    sender: '+6281234567890',
    to: '+628109998877',
    message: 'Halo! Tes WhatsApp.'
);

if (!$ok) {
    // log or retry
}
```

### Group message

```php
$ok = Efihub::whatsapp()->sendGroupMessage(
    sender: '+6281234567890',
    to: 'group-abc123', // group identifier
    message: 'Halo semua!'
);
```

### Attachment (single file)

Supports the same flexible file specs as `postMultipart()`:

```php
// Path string
$ok = Efihub::whatsapp()->sendAttachment(
    sender: '+6281234567890',
    to: '+628109998877',
    message: 'Berikut invoice',
    attachment: storage_path('app/invoices/jan.pdf'),
);

// Raw contents with custom filename & headers
$ok = Efihub::whatsapp()->sendAttachment(
    sender: '+6281234567890',
    to: '+628109998877',
    message: 'Data CSV',
    attachment: [
        'contents' => file_get_contents(storage_path('app/tmp/report.csv')),
        'filename' => 'report.csv',
        'headers' => ['Content-Type' => 'text/csv'],
    ],
);
```

### Multiple attachments to a group

```php
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

### File spec formats

- `/path/to/file.ext`
- `[ 'path' => '/path/to/file.ext', 'filename' => 'custom.ext', 'headers' => ['Content-Type' => 'application/pdf'] ]`
- `[ 'contents' => $binaryOrString, 'filename' => 'name.ext', 'headers' => [...] ]`
- `[ '/path/a.jpg', '/path/b.jpg' ]` (multiple files)

### Reference metadata

Optional `ref_id` / `ref_url` let you correlate outbound messages with internal entities (orders, tickets, etc.). Include them when auditing or reconciling.

### Error inspection

The helpers only return boolean. For full details grab the raw response:

```php
$client = app(\Efihub\EfihubClient::class);
$response = $client->post('/whatsapp/send_message', [ /* payload */ ]);
if ($response->failed()) {
    logger()->error('WA send failed', [
        'status' => $response->status(),
        'body' => $response->json(),
    ]);
}
```

### Endpoints used

- `POST /whatsapp/send_message`
- `POST /whatsapp/group/send_message`
- `POST /whatsapp/send_message_with_attachment`
- `POST /whatsapp/group/send_message_with_attachment`

> Adjust paths if your EFIHUB deployment customizes routing.

## License & Author

MIT © Imam Nurcholis. See [LICENSE](LICENSE).

- Author: https://github.com/imamnc
- EFIHUB: https://efihub.morefurniture.id
