<?php

declare(strict_types=1);

namespace swentel\nostr\Relay;

use swentel\nostr\MessageInterface;
use swentel\nostr\RelayInterface;
use swentel\nostr\CommandResultInterface;
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

    /**
     * {@inheritdoc}
     */
    public function setUrl(string $url): void
    {
        // TODO validate this URL which has to start with a prefix ws:// or wss://.
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
    public function send(): CommandResultInterface
    {
        try {
            $client = new WebSocket\Client($this->url);
            $client->text($this->payload);
            $response = $client->receive();
            $client->disconnect();
            $response = json_decode($response->getContent());
            if ($response[0] === 'NOTICE') {
                throw new \RuntimeException($response[1]);
            }
        } catch (WebSocket\Exception\ClientException $e) {
            $response = [
                'ERROR',
                '',
                false,
                $e->getMessage(),
            ];
        }
        return new CommandResult($response);
    }
}
