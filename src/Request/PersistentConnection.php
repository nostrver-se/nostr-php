<?php

declare(strict_types=1);

namespace swentel\nostr\Request;

use swentel\nostr\MessageInterface;
use swentel\nostr\Relay\Relay;
use swentel\nostr\Relay\RelaySet;
use swentel\nostr\RelayResponse\RelayResponse;
use WebSocket\Client;
use WebSocket\Connection;
use WebSocket\Message\Text;

/**
 * PersistentConnection class.
 *
 * With this class you can create a persistent connection with a relay to transmit messages back and forth.
 * This means the connection keeps open to send messages between the client and the relay (websocket server) as long as this is possible.
 * Keep in mind that this library will not close this connection. This needs to be done in your logic.
 */
class PersistentConnection
{
    /**
     * @var Client
     */
    protected Client $websocketClient;

    /**
     * Message that is sent to the relay.
     *
     * @var string
     */
    private string $payload;

    /**
     * Array with all responses (RelayResponses) received from the relay.
     *
     * @var array
     */
    protected array $responses;

    /**
     * https://github.com/sirn-se/websocket-php/blob/v3.4-main/docs/Client.md#persistent-connection
     * Persistent connection
     * If set to true, the underlying connection will be kept open if possible.
     * This means that if Client closes and is then restarted, it may use the same connection.
     * Do not change unless you have a strong reason to do so.
     *
     * @var bool
     */
    private bool $persistent;

    /**
     * Constructor for the Request class.
     * Initializes the url and payload properties based on the provided websocket and message.
     */
    public function __construct(Relay|RelaySet $relay, MessageInterface $message)
    {
        $this->payload = $message->generate();
        $this->websocketClient = $relay->getClient();
        $this->setPersistent();
    }

    /**
     * For transmitting messages between the client and relay.
     *
     * @return array
     */
    public function transmit(): array
    {
        $this->websocketClient->setPersistent($this->isPersistent());
        try {
            $this->websocketClient->text($this->payload);
            $this->websocketClient->onText(function (Client $client, Connection $connection, Text $message) {
                $this->responses[] = RelayResponse::create(json_decode($message->getContent()));
                $res = end($this->responses);
                if (isset($res->event->content)) {
                    print $res->event->content . PHP_EOL;
                }
            })->start();
        } catch (\Exception $e) {
            throw new \RuntimeException($e->getMessage());
        } catch (\Throwable $e) {
            throw new \RuntimeException($e->getMessage());
        }
        return $this->responses;
    }

    private function setPersistent(bool $persistent = true): void
    {
        $this->persistent = $persistent;
    }

    private function isPersistent(): bool
    {
        return $this->persistent;
    }

}
