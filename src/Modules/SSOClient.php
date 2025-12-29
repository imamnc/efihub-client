<?php

namespace Efihub\Modules;

use Efihub\EfihubClient;

/**
 * SSO Service client for EFIHUB.
 *
 * Base path: /sso
 */
class SSOClient
{
    /** @var EfihubClient */
    private $client;

    public function __construct(EfihubClient $client)
    {
        $this->client = $client;
    }

    /**
     * Generate authorization URL for SSO login.
     * @return string|false Authorization URL on success, false on failure
     */
    public function login()
    {
        $clientId = config('efihub.client_id');
        $payload = array_merge([
            'client_id' => $clientId,
        ]);

        // Make the request to generate the SSO URL
        $res = $this->client->post('/sso/authorize', $payload);

        // Check for successful response
        if (!$res->successful()) {
            return false;
        }

        // Extract and return the authorization URL
        $response = $res->json('data');
        return $response['authorization_url'] ?? false;
    }

    /**
     * Handle SSO callback and retrieve user information.
     *
     * @param string $redirectToken The redirect token received from SSO callback
     * @return array|false User information array on success, false on failure
     */
    public function userData(string $redirectToken)
    {
        // Make the request to handle the SSO callback
        $res = $this->client->get('/sso/user', [
            'redirect_token' => $redirectToken,
        ]);

        // Check for successful response
        if (!$res->successful()) {
            return false;
        }

        // Extract user information
        $user = $res->json('data');

        // Return user information
        return $user;
    }
}
