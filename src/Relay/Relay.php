<?php

declare(strict_types=1);

namespace swentel\nostr\Relay;

use swentel\nostr\MessageInterface;
use swentel\nostr\RelayInterface;
use swentel\nostr\CommandResultInterface;
use swentel\nostr\RelayResponse\RelayResponseOk;
use swentel\nostr\RelayResponse\RelayResponseNotice;
use swentel\nostr\RelayResponse\RelayResponse;
use swentel\nostr\RelayResponseInterface;
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
     * Relay constructor.
     *
     * @param string $websocket
     *   The socket URL.
     */
    public function __construct(string $websocket, MessageInterface $message = null)
    {
        $this->url = $websocket;
        // Backwards compatibility for version <1.2.4
        if ($message !== null) {
            $this->setMessage($message);
        }
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
     * @inheritDoc
     */
    public function send(): RelayResponse
    {
        $this->validateUrl();

        try {
            $client = new WebSocket\Client($this->url);
            $client->text($this->payload);
            $response = $client->receive();
            $client->disconnect();
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
}
