<?php

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

    /**
     * Send a multipart/form-data POST request with file attachment(s).
     *
     * Files can be provided in a few formats:
     * - 'field' => '/absolute/or/relative/path/to/file.ext'
     * - 'field' => [ 'path' => '/path/to/file.ext', 'filename' => 'optional.ext', 'headers' => ['Content-Type' => 'application/pdf'] ]
     * - 'field' => [ 'contents' => $binaryOrString, 'filename' => 'name.ext', 'headers' => [...] ]
     * - 'field' => [ '/path/a.pdf', '/path/b.pdf' ] (multiple files for the same field name)
     * - 'field' => [ [ 'path' => '/path/a.pdf' ], [ 'path' => '/path/b.pdf', 'filename' => 'b.pdf' ] ]
     *
     * Note: For raw contents, prefer the associative format with the 'contents' key to avoid ambiguity.
     */
    public function postMultipart(string $endpoint, array $fields = [], array $files = [])
    {
        $buildAndSend = function (string $token) use ($endpoint, $fields, $files) {
            $request = Http::withToken($token);

            foreach ($files as $name => $spec) {
                // Multiple files for the same field if given as a simple list (e.g. ['a.pdf', 'b.pdf'])
                if (is_array($spec) && !isset($spec['path']) && !isset($spec['contents']) && isset($spec[0])) {
                    foreach ($spec as $single) {
                        [$contents, $filename, $headers] = $this->resolveFileSpec($name, $single);
                        $request->attach($name, $contents, $filename, $headers);
                    }
                } else {
                    [$contents, $filename, $headers] = $this->resolveFileSpec($name, $spec);
                    $request->attach($name, $contents, $filename, $headers);
                }
            }

            return $request->post(
                rtrim(config('efihub.api_base_url'), '/') . '/' . ltrim($endpoint, '/'),
                $fields
            );
        };

        $token = $this->getAccessToken();
        $response = $buildAndSend($token);

        if ($response->status() === 401) {
            Cache::forget('efihub_access_token');
            $token = $this->getAccessToken();
            $response = $buildAndSend($token);
        }

        return $response;
    }

    /**
     * Normalize a file specification into [contents, filename, headers] for attach().
     *
     * @param string $field
     * @param mixed  $spec
     * @return array{0:mixed,1:string|null,2:array}
     */
    private function resolveFileSpec(string $field, $spec): array
    {
        $headers = [];

        // String: treat as file path
        if (is_string($spec)) {
            $path = $spec;
            if (!is_readable($path)) {
                throw new \InvalidArgumentException("File for field '{$field}' not readable: {$path}");
            }
            $stream = fopen($path, 'r');
            $filename = basename($path);
            return [$stream, $filename, $headers];
        }

        // Assoc array with 'path'
        if (is_array($spec) && array_key_exists('path', $spec)) {
            $path = $spec['path'];
            if (!is_readable($path)) {
                throw new \InvalidArgumentException("File for field '{$field}' not readable: {$path}");
            }
            $stream = fopen($path, 'r');
            $filename = $spec['filename'] ?? basename($path);
            $headers = $spec['headers'] ?? [];
            return [$stream, $filename, $headers];
        }

        // Assoc array with raw 'contents'
        if (is_array($spec) && array_key_exists('contents', $spec)) {
            $contents = $spec['contents'];
            $filename = $spec['filename'] ?? $field;
            $headers = $spec['headers'] ?? [];
            return [$contents, $filename, $headers];
        }

        throw new \InvalidArgumentException("Invalid file specification for field '{$field}'.");
    }
}
