<?php

declare(strict_types=1);

namespace swentel\nostr\Relay;

use swentel\nostr\MessageInterface;
use swentel\nostr\RelayInterface;
use swentel\nostr\RelayResponse\RelayResponse;
use WebSocket;

class Relay implements RelayInterface
{
    /**
     * The relay URL.
     *
     * @var string
     */
    private string $url;

    /**
     * The message to be sent.
     *
     * @var MessageInterface
     */
    protected MessageInterface $message;

    /**
     * The payload to be sent.
     *
     * @var string
     */
    private string $payload;

    /**
     * The WebSocket client.
     *
     * @var WebSocket\Client|null
     */
    private ?WebSocket\Client $client = null;

    /**
     * Relay constructor.
     *
     * @param string $url
     *   The socket URL.
     */
    public function __construct(string $url, MessageInterface|null $message = null)
    {
        $this->url = $url;
        $this->validateUrl();
        // Backwards compatibility for version <1.2.4
        if ($message !== null) {
            $this->setMessage($message);
        }
        $this->client = new WebSocket\Client($this->url);
    }

    private function validateUrl(): void
    {
        if (!preg_match('/^(ws|wss):\/\//', $this->url)) {
            throw new \InvalidArgumentException('Invalid URL format. URL must start with ws:// or wss://');
        }
    }

    /**
     * {@inheritdoc}
     */
    public function setUrl(string $url): void
    {
        $this->validateUrl();
        $this->url = $url;
    }

    /**
     * {@inheritdoc}
     */
    public function getUrl(): string
    {
        return $this->url;
    }

    /**
     * {@inheritdoc}
     */
    public function setMessage(MessageInterface $message): void
    {
        $this->setPayload($message->generate());
        $this->message = $message;
    }

    /**
     * @param string $payload
     */
    private function setPayload(string $payload): void
    {
        $this->payload = $payload;
    }

    /**
     * {@inheritdoc}
     */
    public function send(): RelayResponse
    {
        // TODO: deprecate this and replace with $request->send($relay, $message) logic.

        try {
            if ($this->client === null) {
                $this->client = new WebSocket\Client($this->url);
            }
            $this->client->text($this->payload);
            $response = $this->client->receive();
            $this->client->disconnect();
            if ($response === null) {
                throw new \RuntimeException('Websocket client response is null');
            }
            $result = RelayResponse::create(json_decode($response->getContent()));
        } catch (WebSocket\Exception\ClientException $e) {
            $result = [
                'ERROR',
                '',
                false,
                $e->getMessage(),
            ];
        }
        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function connect(): void
    {
        $this->validateUrl();
        $this->client = new WebSocket\Client($this->url);
        $this->client->connect();
    }

    /**
     * {@inheritdoc}
     */
    public function isConnected(): bool
    {
        if ($this->client === null) {
            return false;
        }
        return $this->client->isConnected();
    }

    /**
     * {@inheritdoc}
     */
    public function disconnect(): void
    {
        if ($this->isConnected()) {
            $this->client->disconnect();
        }
    }

    /**
     * Get the WebSocket client.
     *
     * @return WebSocket\Client
     */
    public function getClient(): WebSocket\Client
    {
        return $this->client;
    }
}
