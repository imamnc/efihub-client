<?php

namespace Efihub\Modules;

use Efihub\EfihubClient;

/**
 * Websocket Service client for EFIHUB.
 *
 * Base path: /websocket
 */
class WebsocketClient
{
    /** @var EfihubClient */
    private $client;

    public function __construct(EfihubClient $client)
    {
        $this->client = $client;
    }

    /**
     * Dispatch an event to a websocket channel.
     *
     * @param string $channel Channel name, e.g., "orders:updates"
     * @param string $event   Event name, e.g., "OrderUpdated"
     * @param mixed  $data    Arbitrary payload to send to subscribers
     * @param array  $extra   Extra fields if the API accepts more, merged into the payload
     * @return bool           True on success, false on failure
     */
    /**
     * @param mixed $data
     */
    public function dispatch(string $channel, string $event, $data, array $extra = []): bool
    {
        $payload = array_merge([
            'channel' => $channel,
            'event' => $event,
            'data' => $data,
        ], $extra);

        $res = $this->client->post('/websocket/dispatch', $payload);
        if (!$res->successful()) {
            return false;
        }

        $success = $res->json('success');
        return is_bool($success) ? $success : true;
    }
}
