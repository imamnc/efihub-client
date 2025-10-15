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

SDK modern untuk integrasi ke platform EFIHUB menggunakan OAuth 2.0 Client Credentials flow. Menghadirkan helper HTTP sederhana (GET/POST/PUT/DELETE), manajemen token otomatis, dan integrasi native dengan ekosistem Laravel.

[EFIHUB](https://efihub.morefurniture.id/) adalah platform integrasi terpusat milik PT EFI yang menghubungkan berbagai aplikasi EFI dalam satu ekosistem. Menyediakan:

- API Sharing Platform: API yang discoverable dan aman antar aplikasi internal
- Central Webhook Hub: notifikasi real-time dengan routing, retry, dan logging
- Central Scheduler: otomasi task, cron jobs, background processes
- Enterprise Security: OAuth 2.0, JWT, audit trail
- Unified Dashboard: observability untuk API, webhook, scheduler, dan lainnya

Pustaka ini, `imamnc/efihub-client`, adalah klien resmi PHP/Laravel untuk REST API EFIHUB. Autentikasi menggunakan OAuth 2.0 Client Credentials dan menyediakan helper HTTP GET/POST/PUT/DELETE.

Catatan penting: Karena Client Credentials membutuhkan client secret, library ini hanya untuk lingkungan server-side tepercaya (backend). Simpan kredensial di environment/server, jangan pernah mengirimkannya ke browser/klien publik.

## Fitur

- ✅ OAuth 2.0 Client Credentials authentication
- ✅ Automatic access token management & caching
- ✅ Auto refresh saat 401 (token kadaluarsa) + retry sekali
- ✅ HTTP client wrapper berbasis Laravel `Http` response
- ✅ Facade dan Service Provider (auto-discovery)
- ✅ Konfigurasi berbasis environment

## Persyaratan

- PHP ^8.0
- Laravel ^8.0|^9.0|^10.0|^11.0|^12.0
- Guzzle HTTP ^7.0

## Instalasi

### 1) Install via Composer

```bash
composer require imamnc/efihub-client
```

### 2) Publish konfigurasi

```bash
php artisan vendor:publish --provider="Efihub\EfihubServiceProvider" --tag=config
```

### 3) Environment variables

Tambahkan ke `.env` Anda:

```env
EFIHUB_CLIENT_ID=your_client_id
EFIHUB_CLIENT_SECRET=your_client_secret
EFIHUB_TOKEN_URL=https://efihub.morefurniture.id/oauth/token
EFIHUB_API_URL=https://efihub.morefurniture.id/api
```

## Konfigurasi

File `config/efihub.php` (setelah publish):

```php
return [
    'client_id' => env('EFIHUB_CLIENT_ID'),
    'client_secret' => env('EFIHUB_CLIENT_SECRET'),
    'token_url' => env('EFIHUB_TOKEN_URL', 'https://efihub.morefurniture.id/oauth/token'),
    'api_base_url' => env('EFIHUB_API_URL', 'https://efihub.morefurniture.id/api'),
];
```

Anda bisa override nilai default via `.env` jika endpoint EFIHUB yang digunakan berbeda.

## Quick start

Contoh sederhana menggunakan Facade di controller/service:

```php
use Efihub\Facades\Efihub;

// Mendapatkan daftar user (GET) dengan query params
$response = Efihub::get('/user', ['page' => 1]);

if ($response->successful()) {
    $users = $response->json();
}
```

## Penggunaan

### Laravel Facade

```php
use Efihub\Facades\Efihub;

// GET + query params
$res = Efihub::get('/user', ['page' => 2, 'per_page' => 20]);

// POST JSON body
$res = Efihub::post('/orders', ['sku' => 'ABC', 'qty' => 2]);

// PUT JSON body
$res = Efihub::put('/orders/123', ['qty' => 3]);

// DELETE
$res = Efihub::delete('/orders/123');
```

Catatan: Parameter kedua akan dikirim sebagai query (GET) atau body (POST/PUT) sesuai dengan perilaku Laravel HTTP client.

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

### Response & Error handling

Semua method mengembalikan `Illuminate\Http\Client\Response`:

```php
$res = Efihub::get('/user/123');

if ($res->successful()) {
    $data = $res->json();
} elseif ($res->failed()) {
    // akses detail error dari body/status
    logger()->error('EFIHUB error', [
        'status' => $res->status(),
        'body' => $res->json(),
    ]);
}
```

## Perilaku Autentikasi

- Access token diambil via Client Credentials dan di-cache selama ±55 menit
- Jika request mendapat 401, token akan dihapus, di-refresh, lalu request di-retry 1x otomatis

## API

Semua method berada pada `Efihub\EfihubClient` dan tersedia juga via Facade `Efihub`.

- `get(string $endpoint, array $options = []) : Response`
- `post(string $endpoint, array $options = []) : Response`
- `put(string $endpoint, array $options = []) : Response`
- `delete(string $endpoint, array $options = []) : Response`
- `request(string $method, string $endpoint, array $options = []) : Response`
- `getAccessToken() : string` — mengembalikan access token (cached)

Tipe kembalian: `Illuminate\Http\Client\Response`.

## Testing

Anda dapat memalsukan (fake) request HTTP untuk testing:

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

## Catatan Keamanan

- Jangan gunakan library ini di browser/klien publik. Hanya untuk lingkungan server-side tepercaya.
- Simpan kredensial di environment variable atau secrets manager.

## Kontribusi

1. Fork repository
2. Buat feature branch (`git checkout -b feature/amazing-feature`)
3. Commit (`git commit -m 'Add amazing feature'`)
4. Push (`git push origin feature/amazing-feature`)
5. Buka Pull Request

## Lisensi

MIT © Imam Nurcholis. Lihat file [LICENSE](LICENSE).

## Author & Link

- Author: [Imam Nurcholis](https://github.com/imamnc)
- Website EFIHUB: https://efihub.morefurniture.id
