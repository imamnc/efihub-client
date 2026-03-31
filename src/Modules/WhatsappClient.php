<?php

namespace Efihub\Modules;

use Efihub\EfihubClient;

/**
 * Storage Service client for EFIHUB.
 *
 * Base path: /storage
 */
class WhatsappClient
{
    /** @var EfihubClient */
    private $client;

    public function __construct(EfihubClient $client)
    {
        $this->client = $client;
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
        $res = $this->client->get("/whatsapp/sessions/qrcode/$agentCode");
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
        $res = $this->client->get("/whatsapp/sessions/status/$agentCode");
        if (!$res->successful()) {
            return null;
        }

        return $res->json('data.status') == 'CONNECTED' ? 'connected' : 'disconnected';
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
        $res = $this->client->get("/whatsapp/sessions/user/exists/$agentCode/$number");
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
    public function sendMessage(string $sender, string $to, string $message, $ref_id = null, $ref_url = null): bool
    {
        $payload = [
            'sender' => $sender,
            'to' => $to,
            'message' => $message,
            'ref_id' => $ref_id,
            'ref_url' => $ref_url,
        ];

        // Send request
        $res = $this->client->post('/whatsapp/send_message', $payload);

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
    public function sendGroupMessage(string $sender, string $to, string $message, $ref_id = null, $ref_url = null): bool
    {
        $payload = [
            'sender' => $sender,
            'to' => $to,
            'message' => $message,
            'ref_id' => $ref_id,
            'ref_url' => $ref_url,
        ];

        // Send request
        $res = $this->client->post('/whatsapp/group/send_message', $payload);

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
    /**
     * @param mixed $attachment
     */
    public function sendAttachment(string $sender, string $to, string $message, $attachment, $ref_id = null, $ref_url = null): bool
    {
        $fields = [
            'sender' => $sender,
            'to' => $to,
            'message' => $message,
            'ref_id' => $ref_id,
            'ref_url' => $ref_url,
        ];

        // Send request
        $res = $this->client->postMultipart('/whatsapp/send_message_with_attachment', $fields, [
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
    /**
     * @param mixed $attachment
     */
    public function sendGroupAttachment(string $sender, string $to, string $message, $attachment, $ref_id = null, $ref_url = null): bool
    {
        $fields = [
            'sender' => $sender,
            'to' => $to,
            'message' => $message,
            'ref_id' => $ref_id,
            'ref_url' => $ref_url,
        ];

        // Send request
        $res = $this->client->postMultipart('/whatsapp/group/send_message_with_attachment', $fields, [
            'attachment' => $attachment,
        ]);

        if (!$res->successful()) {
            return false;
        }

        return true;
    }
}
