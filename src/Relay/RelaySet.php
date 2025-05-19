<?php

declare(strict_types=1);

namespace swentel\nostr\Relay;

use swentel\nostr\MessageInterface;
use swentel\nostr\RelayResponse\RelayResponse;
use swentel\nostr\RelaySetInterface;
use WebSocket;

class RelaySet implements RelaySetInterface
{
    /**
     * Array with Relay objects.
     *
     * @var array
     */
    protected array $relays = [];

    /**
     * The message to be sent to all relays.
     *
     * @var MessageInterface
     */
    private MessageInterface $message;

    /**
     * @inheritDoc
     */
    public function setRelays(array $relays): void
    {
        $this->relays = $relays;
    }

    /**
     * @inheritDoc
     */
    public function getRelays(): array
    {
        return $this->relays;
    }

    /**
     * @inheritDoc
     */
    public function addRelay(Relay $relay): void
    {
        $this->relays[] = $relay;
    }

    /**
     * @inheritDoc
     */
    public function removeRelay(Relay $relay): void
    {
        $this->relays = array_filter($this->relays, function ($r) use ($relay) {
            return $r !== $relay;
        });
    }

    /**
     * @inheritDoc
     */
    public function createFromUrls(array|string $urls): void
    {
        foreach ($urls as $url) {
            $relay = new Relay($url);
            $this->relays[] = $relay;
        }
    }

    /**
     * @inheritDoc
     */
    public function setMessage(MessageInterface $message): void
    {
        $this->message = $message;
    }

    /**
     * @inheritDoc
     */
    public function connect($throwOnError = true): bool
    {
        $hasError = false;
        $errors = [];
        foreach ($this->relays as $relay) {
            try {
                $relay->connect();
            } catch (\Exception $e) {
                $hasError = true;
                $errors[] = $relay->getUrl() . ' - ' . $e->getMessage();
            }
        }
        if ($hasError && $throwOnError) {
            throw new \RuntimeException(implode("\n", $errors));
        }
        return !$hasError;
    }

    /**
     * @inheritDoc
     */
    public function disconnect(): bool
    {
        try {
            foreach ($this->relays as $relay) {
                $relay->disconnect();
            }
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * @inheritDoc
     */
    public function isConnected(): bool
    {
        foreach ($this->relays as $relay) {
            if (!$relay->isConnected()) {
                return false;
            }
        }
        return true;
    }

    /**
     * @inheritDoc
     */
    public function send(): array
    {
        // TODO: deprecate this and replace with $request->send($relaySet, $message) logic.
        try {
            // Send message to each relay defined in this set.
            /** @var Relay $relay */
            foreach ($this->relays as $relay) {
                $client = $relay->getClient();
                $payload = $this->message->generate();
                $client->text($payload);
                $response = $client->receive();
                $client->disconnect();
                if ($response->getOpcode() === 'ping') {
                    continue;
                }
                if ($response === null) {
                    throw new \RuntimeException('Websocket client response is null');
                }
                $result[$relay->getUrl()] = RelayResponse::create(json_decode($response->getContent()));
            }
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
