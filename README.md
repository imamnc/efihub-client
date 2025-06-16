<?php
# Laravel EFIHUB Client

Package Laravel untuk integrasi dengan EFIHUB API menggunakan OAuth2 Client Credentials Flow dengan automatic token management dan caching.

## Fitur

- ✅ OAuth2 Client Credentials authentication
- ✅ Automatic access token management dan caching
- ✅ Token refresh otomatis saat expired
- ✅ HTTP client wrapper dengan error handling
- ✅ Laravel Facade support
- ✅ Service Provider auto-discovery
- ✅ Konfigurasi environment-based

## Instalasi

### 1. Install via Composer

```bash
composer require efihub/client
```

### 2. Publish Configuration

```bash
php artisan vendor:publish --provider="Efihub\EfihubServiceProvider" --tag=config
```

### 3. Environment Configuration

Tambahkan konfigurasi berikut ke file `.env`:

```env
EFIHUB_CLIENT_ID=your_client_id
EFIHUB_CLIENT_SECRET=your_client_secret
EFIHUB_TOKEN_URL=https://efihub.morefurniture.id/oauth/token
EFIHUB_API_URL=https://efihub.morefurniture.id/api
```

## Penggunaan

### Menggunakan Facade

```php
use Efihub\Facades\Efihub;

// GET request
$response = Efihub::get('/users');

// POST request
$response = Efihub::post('/users', [
    'name' => 'John Doe',
    'email' => 'john@example.com'
]);

// PUT request
$response = Efihub::put('/users/1', [
    'name' => 'Jane Doe'
]);

// DELETE request
$response = Efihub::delete('/users/1');
```

### Menggunakan Dependency Injection

```php
use Efihub\EfihubClient;

class UserService
{
    public function __construct(private EfihubClient $efihub)
    {
    }

    public function getAllUsers()
    {
        $response = $this->efihub->get('/users');
        
        if ($response->successful()) {
            return $response->json();
        }
        
        throw new Exception('Failed to fetch users');
    }
}
```

### Response Handling

```php
$response = Efihub::get('/users');

// Check if successful
if ($response->successful()) {
    $data = $response->json();
    $status = $response->status();
}

// Handle errors
if ($response->failed()) {
    $error = $response->json();
    Log::error('EFIHUB API Error', $error);
}
```

## Konfigurasi

File konfigurasi tersedia di `config/efihub.php` setelah publishing:

```php
return [
    'client_id' => env('EFIHUB_CLIENT_ID'),
    'client_secret' => env('EFIHUB_CLIENT_SECRET'),
    'token_url' => env('EFIHUB_TOKEN_URL', 'https://efihub.morefurniture.id/oauth/token'),
    'api_base_url' => env('EFIHUB_API_URL', 'https://efihub.morefurniture.id/api'),
];
```

## Token Management

Package ini secara otomatis menangani:

- **Token Caching**: Access token di-cache selama 55 menit
- **Auto Refresh**: Token otomatis di-refresh saat expired (401 response)
- **Error Handling**: Automatic retry dengan token baru jika terjadi 401 error

## API Methods

### HTTP Methods

| Method | Description |
|--------|-------------|
| `get($endpoint, $options = [])` | HTTP GET request |
| `post($endpoint, $options = [])` | HTTP POST request |
| `put($endpoint, $options = [])` | HTTP PUT request |
| `delete($endpoint, $options = [])` | HTTP DELETE request |
| `request($method, $endpoint, $options = [])` | Generic HTTP request |

### Token Management

| Method | Description |
|--------|-------------|
| `getAccessToken()` | Mendapatkan access token (cached) |

## Requirements

- PHP ^8.0
- Laravel ^8.0\|^9.0\|^10.0\|^11.0\|^12.0
- Guzzle HTTP ^7.0

## Contoh Penggunaan Lengkap

```php
<?php

namespace App\Services;

use Efihub\Facades\Efihub;
use Illuminate\Support\Facades\Log;

class EfihubService
{
    public function createUser(array $userData): array
    {
        try {
            $response = Efihub::post('/users', $userData);
            
            if ($response->successful()) {
                return $response->json();
            }
            
            throw new \Exception('Failed to create user: ' . $response->body());
            
        } catch (\Exception $e) {
            Log::error('EFIHUB Create User Error', [
                'error' => $e->getMessage(),
                'data' => $userData
            ]);
            
            throw $e;
        }
    }
    
    public function getUsers(array $filters = []): array
    {
        $response = Efihub::get('/users', $filters);
        
        return $response->successful() 
            ? $response->json() 
            : [];
    }
}
```

## Error Handling

Package ini menangani beberapa skenario error:

1. **Token Fetch Error**: Exception akan di-throw jika gagal mendapatkan access token
2. **401 Unauthorized**: Token otomatis di-refresh dan request di-retry
3. **Network Errors**: Menggunakan Guzzle HTTP error handling

## Testing

Untuk testing, Anda dapat mock HTTP responses:

```php
use Illuminate\Support\Facades\Http;

Http::fake([
    'efihub.morefurniture.id/oauth/token' => Http::response([
        'access_token' => 'fake-token',
        'expires_in' => 3600
    ]),
    'efihub.morefurniture.id/api/*' => Http::response([
        'data' => ['users' => []]
    ])
]);
```

## Contributing

1. Fork repository
2. Buat feature branch (`git checkout -b feature/amazing-feature`)
3. Commit changes (`git commit -m 'Add amazing feature'`)
4. Push ke branch (`git push origin feature/amazing-feature`)
5. Buat Pull Request

## License

Package ini menggunakan lisensi MIT. Lihat file [LICENSE](LICENSE) untuk detail.

## Support

Untuk pertanyaan atau masalah, silakan buat issue di GitHub repository.
