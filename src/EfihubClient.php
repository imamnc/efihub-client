<?

namespace Efihub;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class EfihubClient
{
    public function getAccessToken(): string
    {
        return Cache::remember('efihub_access_token', 55 * 60, function () {
            $response = Http::asForm()->post(config('efihub.token_url'), [
                'client_id' => config('efihub.client_id'),
                'client_secret' => config('efihub.client_secret'),
                'grant_type' => 'client_credentials',
            ]);

            throw_if($response->failed(), new \Exception('Failed to fetch EFIHUB token'));

            return $response->json('access_token');
        });
    }

    public function request(string $method, string $endpoint, array $options = [])
    {
        $token = $this->getAccessToken();

        $response = Http::withToken($token)->$method(
            rtrim(config('efihub.api_base_url'), '/') . '/' . ltrim($endpoint, '/'),
            $options
        );

        if ($response->status() === 401) {
            Cache::forget('efihub_access_token');
            $token = $this->getAccessToken();

            $response = Http::withToken($token)->$method(
                rtrim(config('efihub.api_base_url'), '/') . '/' . ltrim($endpoint, '/'),
                $options
            );
        }

        return $response;
    }

    public function get($endpoint, $options = [])
    {
        return $this->request('get', $endpoint, $options);
    }

    public function post($endpoint, $options = [])
    {
        return $this->request('post', $endpoint, $options);
    }

    public function put($endpoint, $options = [])
    {
        return $this->request('put', $endpoint, $options);
    }

    public function delete($endpoint, $options = [])
    {
        return $this->request('delete', $endpoint, $options);
    }
}
