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
    public function __construct(private EfihubClient $client) {}

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
     * @return bool True on success, false on failure
     */
    public function sendAttachment(string $sender, string $to, string $message, mixed $attachment): bool
    {
        $fields = [
            'sender' => $sender,
            'to' => $to,
            'message' => $message,
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
     * @return bool True on success, false on failure
     */
    public function sendGroupAttachment(string $sender, string $to, string $message, mixed $attachment): bool
    {
        $fields = [
            'sender' => $sender,
            'to' => $to,
            'message' => $message,
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
