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

## Description

A modern SDK for integrating with the EFIHUB platform using the OAuth 2.0 Client Credentials flow. It provides simple HTTP helpers (GET/POST/PUT/DELETE), automatic token management, and native integration with the Laravel ecosystem.

[EFIHUB](https://efihub.morefurniture.id/) is PT EFI’s centralized integration platform that connects multiple EFI applications into a single ecosystem. It offers:

- API Sharing Platform: discoverable and secured APIs across internal apps
- Central Webhook Hub: real-time notifications with routing, retries, and logging
- Central Scheduler: task automation, cron jobs, and background processing
- Enterprise Security: OAuth 2.0, JWT, and audit trails
- Unified Dashboard: observability across APIs, webhooks, schedulers, and more

This package, `imamnc/efihub-client`, is the official PHP/Laravel client for EFIHUB’s REST API. It authenticates using the OAuth 2.0 Client Credentials flow and exposes simple HTTP helpers for GET, POST, PUT and DELETE.

Important: because the Client Credentials flow requires a client secret, this library must only be used in trusted server-side environments (backend). Keep your credentials in environment variables or a secure secrets manager and never expose them to browsers or public clients.

## Features

- ✅ OAuth 2.0 Client Credentials authentication
- ✅ Automatic access token management & caching
- ✅ Automatic refresh on 401 (expired token) with one retry
- ✅ HTTP client wrapper based on Laravel `Http` responses
- ✅ Facade and Service Provider (auto-discovery)
- ✅ Environment-based configuration

## Requirements

- PHP ^8.0
- Laravel ^8.0 | ^9.0 | ^10.0 | ^11.0 | ^12.0
- Guzzle HTTP ^7.0

## Installation

### 1) Install via Composer

```bash
composer require imamnc/efihub-client
```

### 2) Publish configuration

```bash
php artisan vendor:publish --provider="Efihub\EfihubServiceProvider" --tag=config
```

### 3) Environment variables

Add to your `.env`:

```env
EFIHUB_CLIENT_ID=your_client_id
EFIHUB_CLIENT_SECRET=your_client_secret
EFIHUB_TOKEN_URL=https://efihub.morefurniture.id/oauth/token
EFIHUB_API_URL=https://efihub.morefurniture.id/api
```

## Configuration

The `config/efihub.php` file (after publishing):

```php
return [
    'client_id' => env('EFIHUB_CLIENT_ID'),
    'client_secret' => env('EFIHUB_CLIENT_SECRET'),
    'token_url' => env('EFIHUB_TOKEN_URL', 'https://efihub.morefurniture.id/oauth/token'),
    'api_base_url' => env('EFIHUB_API_URL', 'https://efihub.morefurniture.id/api'),
];
```

You can override the defaults via `.env` if you use a different EFIHUB endpoint.

## Quick start

A minimal example using the Facade in a controller or service:

```php
use Efihub\Facades\Efihub;

// Get list of users (GET) with query params
$response = Efihub::get('/user', ['page' => 1]);

if ($response->successful()) {
    $users = $response->json();
}
```

## Usage

### Laravel Facade

```php
use Efihub\Facades\Efihub;

// GET with query params
$res = Efihub::get('/user', ['page' => 2, 'per_page' => 20]);

// POST JSON body
$res = Efihub::post('/orders', ['sku' => 'ABC', 'qty' => 2]);

// PUT JSON body
$res = Efihub::put('/orders/123', ['qty' => 3]);

// DELETE
$res = Efihub::delete('/orders/123');
```

Note: the second parameter will be sent as query parameters for GET requests or as the request body for POST/PUT requests following the Laravel HTTP client behavior.

### Dependency Injection (Service/Controller)

```php
use Efihub\EfihubClient;

class UserService
{
    public function __construct(private EfihubClient $efihub) {}

    public function getAll(): array
    {
        $res = $this->efihub->get('/user');
        return $res->successful() ? $res->json() : [];
    }
}
```

### Multipart upload (file attachments)

Upload one or multiple files with additional fields:

```php
use Efihub\Facades\Efihub;

// Single file by path
$res = Efihub::postMultipart('/documents', [
    'type' => 'invoice',
], [
    'file' => storage_path('app/invoices/jan.pdf'),
]);

// Multiple files and custom filenames
$res = Efihub::postMultipart('/documents/bulk', [
    'batch' => '2025-10',
], [
    'files' => [
        ['path' => storage_path('app/invoices/jan.pdf')],
        ['path' => storage_path('app/invoices/feb.pdf'), 'filename' => 'invoice-feb.pdf'],
    ],
]);

// Raw contents
$res = Efihub::postMultipart('/upload', [
    'note' => 'generated on the fly',
], [
    'file' => [
        'contents' => file_get_contents(storage_path('app/tmp/report.csv')),
        'filename' => 'report.csv',
        'headers' => ['Content-Type' => 'text/csv'],
    ],
]);
```

Supported file specs:

- `'field' => '/path/to/file.ext'`
- `'field' => ['path' => '/path/to/file.ext', 'filename' => 'optional.ext', 'headers' => [...]]`
- `'field' => ['contents' => $binaryOrString, 'filename' => 'name.ext', 'headers' => [...]]`
- `'field' => ['/path/a.pdf', '/path/b.pdf']` (multiple files for the same field)

Note: for raw contents, use the associative format with the `contents` key to avoid ambiguity.

### Response & error handling

All methods return `Illuminate\Http\Client\Response`:

```php
$res = Efihub::get('/user/123');

if ($res->successful()) {
    $data = $res->json();
} elseif ($res->failed()) {
    // access error details from body/status
    logger()->error('EFIHUB error', [
        'status' => $res->status(),
        'body' => $res->json(),
    ]);
}
```

## Authentication behavior

- Access tokens are obtained via Client Credentials and cached (approx. 55 minutes)
- If a request returns 401, the token is cleared, refreshed, and the request is retried once automatically

## API

All methods live on `Efihub\\EfihubClient` and are also available via the `Efihub` Facade.

- `get(string $endpoint, array $options = []) : Response`
- `post(string $endpoint, array $options = []) : Response`
- `put(string $endpoint, array $options = []) : Response`
- `delete(string $endpoint, array $options = []) : Response`
- `postMultipart(string $endpoint, array $fields = [], array $files = []) : Response` — send multipart/form-data with file attachment(s)
- `request(string $method, string $endpoint, array $options = []) : Response`
- `getAccessToken() : string` — returns the cached access token

Return type: `Illuminate\\Http\\Client\\Response`.

## Testing

You can fake HTTP requests for testing:

```php
use Illuminate\Support\Facades\Http;

Http::fake([
    'efihub.morefurniture.id/oauth/token' => Http::response([
        'access_token' => 'fake-token',
        'expires_in' => 3600,
    ], 200),
    'efihub.morefurniture.id/api/*' => Http::response([
        'data' => ['users' => []],
    ], 200),
]);
```

## Security notes

- Do not use this library in browser/public clients. It is intended for trusted server-side environments only.
- Store credentials in environment variables or a secure secrets manager.

## Contributing

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add amazing feature'`)
4. Push the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

## License

MIT © Imam Nurcholis. See the [LICENSE](LICENSE) file for details.

## Author & links

- Author: [Imam Nurcholis](https://github.com/imamnc)
- EFIHUB: https://efihub.morefurniture.id
