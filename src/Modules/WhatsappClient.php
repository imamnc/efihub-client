<?php

namespace Efihub\Modules;

use Efihub\EfihubClient;

/**
 * Whatsapp Service client for EFIHUB.
 *
 * Base path: /whatsapp
 */
class WhatsappClient
{
    /** @var EfihubClient */
    private $client;

    public function __construct(EfihubClient $client)
    {
        $this->client = $client;
    }

    public function normalizePhoneNumber(string $phone): string
    {
        // Only if the phone number starts with '0' or '+', replace it with '62'
        if (preg_match('/^(0|\+)/', $phone)) {
            // Expected 62xxxxxxxxxxxx
            $normalized = preg_replace('/^(0|\+)/', '62', $phone);
            return $normalized;
        } else {
            // If it doesn't start with '0' or '+', assume it's already in correct format
            return $phone;
        }
    }

    /**
     * Get all Whatsapp Agents
     *
     * @return array List of agents, empty array on failure
     */
    public function agents(): array
    {
        $res = $this->client->get('/whatsapp/sessions');
        if (!$res->successful()) {
            return [];
        }

        return $res->json('data') ?? [];
    }

    /**
     * Get QR code for a Whatsapp agent.
     *
     * @param string $agentCode Agent code to get QR code
     * @return string|null image/png;base64 string on success, null on failure
     */
    public function agentQR(string $agentCode): ?string
    {
        $res = $this->client->get("/whatsapp/session/qrcode/$agentCode");
        if (!$res->successful()) {
            return null;
        }

        return $res->body();
    }

    /**
     * Get session status for a Whatsapp agent.
     *
     * @param string $agentCode Agent code to check status
     * @return string|null Session status (e.g. 'connected', 'disconnected') on success, null on failure
     */
    public function agentStatus(string $agentCode): ?string
    {
        $res = $this->client->get("/whatsapp/session/status/$agentCode");
        if (!$res->successful()) {
            return null;
        }

        return $res->json('data.status') == 'CONNECTED' ? 'connected' : 'disconnected';
    }

    /**
     * Start a Whatsapp agent session.
     *
     * @param string $agentCode Agent code to start session
     * @return bool True if session started successfully, false on failure
     */
    public function agentStart(string $agentCode): bool
    {
        $res = $this->client->post("/whatsapp/session/start/$agentCode");
        if (!$res->successful()) {
            return false;
        }

        return $res->json('data') == true;
    }

    /**
     * Restart a Whatsapp agent session.
     *
     * @param string $agentCode Agent code to restart session
     * @return bool True if session restarted successfully, false on failure
     */
    public function agentRestart(string $agentCode): bool
    {
        $res = $this->client->post("/whatsapp/session/restart/$agentCode");
        if (!$res->successful()) {
            return false;
        }

        return $res->json('data') == true;
    }

    /**
     * Terminate a Whatsapp agent session.
     *
     * @param string $agentCode Agent code to terminate session
     * @return bool True if session terminated successfully, false on failure
     */
    public function agentTerminate(string $agentCode): bool
    {
        $res = $this->client->post("/whatsapp/session/terminate/$agentCode");
        if (!$res->successful()) {
            return false;
        }

        return $res->json('data') == true;
    }

    /**
     * Check if a phone number is valid for a Whatsapp agent.
     *
     * @param string $agentCode Agent code to check against
     * @param string $number Phone number to validate
     * @return bool True if valid, false on failure or invalid number
     */
    public function checkPhoneNumber(string $agentCode, string $number): bool
    {
        $normalizedNumber = $this->normalizePhoneNumber($number);
        $res = $this->client->get("/whatsapp/user/exists/$agentCode/$normalizedNumber");
        if (!$res->successful()) {
            return false;
        }

        return $res->json('data.valid') === true;
    }

    /**
     * Send message via Whatsapp.
     *
     * @param string $sender Sender phone number
     * @param string $to Recipient phone number or group ID
     * @param string $message Text message content
     * @param string|null $ref_id Optional reference ID
     * @param string|null $ref_url Optional reference URL
     * @return bool True on success, false on failure
     */
    public function sendMessage(string $sender, string $to, string $message, ?string $ref_id = null, ?string $ref_url = null): bool
    {
        $payload = [
            'sender' => $sender,
            'to' => $this->normalizePhoneNumber($to),
            'message' => $message,
            'ref_id' => $ref_id,
            'ref_url' => $ref_url,
        ];

        // Send request
        $res = $this->client->post('/whatsapp/message', $payload);

        if (!$res->successful()) {
            return false;
        }

        return true;
    }

    /**
     * Send message to group via Whatsapp.
     *
     * @param string $sender Sender phone number
     * @param string $to Recipient phone number or group ID
     * @param string $message Text message content
     * @param string|null $ref_id Optional reference ID
     * @param string|null $ref_url Optional reference URL
     * @return bool True on success, false on failure
     */
    public function sendGroupMessage(string $sender, string $to, string $message, ?string $ref_id = null, ?string $ref_url = null): bool
    {
        $payload = [
            'sender' => $sender,
            'to' => $this->normalizePhoneNumber($to),
            'message' => $message,
            'ref_id' => $ref_id,
            'ref_url' => $ref_url,
        ];

        // Send request
        $res = $this->client->post('/whatsapp/message/group', $payload);

        if (!$res->successful()) {
            return false;
        }

        return true;
    }

    /**
     * Send message via Whatsapp with attachment.
     *
     * @param string $sender Sender phone number
     * @param string $to Recipient phone number or group ID
     * @param string $message Text message content
     * @param mixed $attachment File spec accepted by EfihubClient::postMultipart (string path, assoc with 'path'/'contents', or array list)
     * @param string|null $ref_id Optional reference ID
     * @param string|null $ref_url Optional reference URL
     * @return bool True on success, false on failure
     */
    public function sendAttachment(string $sender, string $to, string $message, $attachment, ?string $ref_id = null, ?string $ref_url = null): bool
    {
        $fields = [
            'sender' => $sender,
            'to' => $this->normalizePhoneNumber($to),
            'message' => $message,
            'ref_id' => $ref_id,
            'ref_url' => $ref_url,
        ];

        // Send request
        $res = $this->client->postMultipart('/whatsapp/message/attachment', $fields, [
            'attachment' => $attachment,
        ]);

        if (!$res->successful()) {
            return false;
        }

        return true;
    }

    /**
     * Send message via Whatsapp with attachment.
     *
     * @param string $sender Sender phone number
     * @param string $to Recipient phone number or group ID
     * @param string $message Text message content
     * @param mixed $attachment File spec accepted by EfihubClient::postMultipart (string path, assoc with 'path'/'contents', or array list)
     * @param string|null $ref_id Optional reference ID
     * @param string|null $ref_url Optional reference URL
     * @return bool True on success, false on failure
     */
    public function sendGroupAttachment(string $sender, string $to, string $message, $attachment, ?string $ref_id = null, ?string $ref_url = null): bool
    {
        $fields = [
            'sender' => $sender,
            'to' => $this->normalizePhoneNumber($to),
            'message' => $message,
            'ref_id' => $ref_id,
            'ref_url' => $ref_url,
        ];

        // Send request
        $res = $this->client->postMultipart('/whatsapp/message/group/attachment', $fields, [
            'attachment' => $attachment,
        ]);

        if (!$res->successful()) {
            return false;
        }

        return true;
    }

    /**
     * Get recent messages for a Whatsapp agent and phone number.
     *
     * @param string $agentCode Agent code to fetch messages for
     * @param string $phone Phone number to fetch messages for
     * @param int $limit Number of recent messages to retrieve (default 10)
     * @return array List of messages, empty array on failure
     */
    public function getMessages(string $agentCode, string $phone, int $limit = 10): array
    {
        // Remove leading '+' if present, as API expects raw numbers
        $normalizedPhone = $this->normalizePhoneNumber($phone);

        // Send request
        $res = $this->client->get("/whatsapp/messages/$agentCode/$normalizedPhone/$limit");
        if (!$res->successful()) {
            return [];
        }

        return $res->json('data') ?? [];
    }

    /**
     * Download media for a specific Whatsapp message.
     * 
     * @param string $agentCode
     * @param string $phone
     * @param string $messageId
     * @return object|null
     */
    public function downloadMedia(string $agentCode, string $phone, string $messageId): ?object
    {
        // Normalize phone number
        $normalizedPhone = $this->normalizePhoneNumber($phone);
        // Send request
        $res = $this->client->get("/whatsapp/message/download/{$agentCode}/{$normalizedPhone}/{$messageId}");
        if (!$res->successful()) {
            return null;
        }

        return $res->object()->data ?? null;
    }
}
