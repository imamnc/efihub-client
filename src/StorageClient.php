<?php

namespace Efihub;

/**
 * Storage Service client for EFIHUB.
 *
 * Base path: /storage
 */
class StorageClient
{
    public function __construct(private EfihubClient $client) {}

    /**
     * Upload a file to storage.
     *
     * @param mixed $file File spec accepted by EfihubClient::postMultipart (string path, assoc with 'path'/'contents', or array list)
     * @param string $path Destination path or directory (end with '/' to auto-generate filename)
     * @param array $fields Additional form fields if needed
     * @return string|false URL of uploaded file on success, false on failure
     */
    public function upload(mixed $file, string $path, array $fields = []): string|false
    {
        $fields = array_merge(['path' => $path], $fields);

        $res = $this->client->postMultipart('/storage/upload', $fields, [
            'file' => $file,
        ]);

        if (!$res->successful()) {
            return false;
        }

        return $res->json('data.url');
    }

    /**
     * Get a public URL for a stored file.
     * Returns null when not found.
     * @return string|null
     */
    public function url(string $path): ?string
    {
        $res = $this->client->get('/storage/url', ['path' => $path]);
        if (!$res->successful()) {
            return null;
        }

        // Try common shapes: { data: { url } } or { url } or { data: "https://..." }
        return $res->json('data.url')
            ?? $res->json('url')
            ?? (is_string($res->json('data')) ? $res->json('data') : null);
    }

    /**
     * Check whether a file exists on storage.
     * @return bool
     */
    public function exists(string $path): bool
    {
        $res = $this->client->get('/storage/exists', ['path' => $path]);
        if ($res->status() === 404) {
            return false;
        }
        if (!$res->successful()) {
            return false;
        }

        $exists = $res->json('exists')
            ?? $res->json('data.exists')
            ?? $res->json('data');

        return (bool) $exists;
    }

    /**
     * Get file size in bytes. Returns null if not found.
     * @return int|null
     */
    public function size(string $path): ?int
    {
        $res = $this->client->get('/storage/size', ['path' => $path]);
        if (!$res->successful()) {
            return null;
        }

        $size = $res->json('data.size')
            ?? $res->json('data.bytes')
            ?? $res->json('size')
            ?? $res->json('bytes')
            ?? $res->json('data');

        return is_numeric($size) ? (int) $size : null;
    }

    /**
     * Delete a file by path. Returns true when deletion succeeds.
     * @return bool
     */
    public function delete(string $path): bool
    {
        $res = $this->client->delete('/storage/delete', ['path' => $path]);
        if (!$res->successful()) {
            return false;
        }

        $success = $res->json('success');
        return is_bool($success) ? $success : true; // default to true on 2xx
    }
}
