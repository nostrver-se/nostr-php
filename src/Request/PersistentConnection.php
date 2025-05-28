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
    protected array $responses = [];

    /**
     * Array of callback functions (closures) to be called when messages are received.
     *
     * @var callable[]
     */
    protected array $messageCallbacks = [];

    /**
     * Whether to print received messages to stdout.
     *
     * @var bool
     */
    protected bool $printMessages = false;

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
     * Adds a callback function to be called when messages are received.
     *
     * The callback will receive the RelayResponse object as its parameter.
     *
     * @param callable $callback (Closure) function to be called with the RelayResponse
     * @return self
     */
    public function onReceive(callable $callback): self
    {
        $this->messageCallbacks[] = $callback;
        return $this;
    }

    /**
     * Enable or disable printing messages to stdout.
     *
     * @param bool $enable Whether to print messages
     * @return self
     */
    public function setPrintMessages(bool $enable = true): self
    {
        $this->printMessages = $enable;
        return $this;
    }

    /**
     * For transmitting messages between the client and relay.
     *
     * @return array
     */
    public function transmit(): array
    {
        if (!$this->websocketClient->isConnected()) {
            $this->websocketClient->connect();
        }
        $this->websocketClient->setPersistent($this->isPersistent());
        try {
            $this->websocketClient->text($this->payload);
            $this->websocketClient->onText(function (Client $client, Connection $connection, Text $message) {
                $relayResponse = RelayResponse::create(json_decode($message->getContent()));
                $this->responses[] = $relayResponse;

                // Print message if enabled
                if ($this->printMessages && isset($relayResponse->event->content)) {
                    print $relayResponse->event->content . PHP_EOL;
                }

                // Call all registered callbacks
                foreach ($this->messageCallbacks as $callback) {
                    $callback($relayResponse);
                }
            })->start();
        } catch (\Exception $e) {
            throw new \RuntimeException($e->getMessage());
        } catch (\Throwable $e) {
            throw new \RuntimeException($e->getMessage());
        }
        return $this->responses;
    }

    /**
     * Clear all registered message callbacks.
     *
     * @return self
     */
    public function clearCallbacks(): self
    {
        $this->messageCallbacks = [];
        return $this;
    }

    private function setPersistent(bool $persistent = true): void
    {
        $this->persistent = $persistent;
    }

    private function isPersistent(): bool
    {
        return $this->persistent;
    }

    /**
     * Pause the socket connection receiving messages.
     *
     * @return void
     */
    public function pause(): void
    {
        if ($this->websocketClient->isRunning()) {
            $this->websocketClient->stop();
        }
    }

    /**
     * Resume paused socket connection to start receiving messages again.
     *
     * @return void
     * @throws \Throwable
     */
    public function resume(): void
    {
        if (!$this->websocketClient->isRunning()) {
            $this->websocketClient->start();
        }
    }

    /**
     * Disconnect and close the socket connection.
     *
     * @return void
     */
    public function close(): void
    {
        if ($this->websocketClient->isConnected()) {
            $this->websocketClient->disconnect();
        }
        $this->websocketClient->close();
    }
}
