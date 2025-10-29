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

Designed for server-side apps only—do not expose your client secret to browsers.

## Installation

1. Install the package

```bash
composer require imamnc/efihub-client
```

2. Publish config (optional; auto-discovery is enabled)

```bash
php artisan vendor:publish --provider="Efihub\EfihubServiceProvider" --tag=config
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
```

Dependency Injection example:

```php
use Efihub\EfihubClient;

class UserService
{
    public function __construct(private EfihubClient $efihub) {}

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
        $resp = Efihub::storage()->upload($uploadedFile, 'uploads/'.date('Y/m/d').'/');

        if ($resp->failed()) {
            return back()->withErrors($resp->json('message') ?? 'Upload failed');
        }

        $path = $resp->json('data.path') ?? $resp->json('path');
        $url  = $path ? Efihub::storage()->url($path) : null;

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

Efihub::socket()->dispatch(
    channel: 'orders:updates',
    event: 'OrderUpdated',
    data: ['order_id' => 123, 'status' => 'updated']
);
```

Endpoint used: POST `/api/websocket/dispatch` with JSON `{ channel, event, data }`.

## License & Author

MIT © Imam Nurcholis. See [LICENSE](LICENSE).

- Author: https://github.com/imamnc
- EFIHUB: https://efihub.morefurniture.id
