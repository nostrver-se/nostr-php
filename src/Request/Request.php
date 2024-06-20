<?php

declare(strict_types=1);

namespace swentel\nostr\Request;

use swentel\nostr\Relay\Relay;
use swentel\nostr\Relay\RelaySet;
use swentel\nostr\RequestInterface;
use WebSocket;

class Request implements RequestInterface
{
    /**
     * RelaySet.
     *
     * @var RelaySet
     */
    protected RelaySet $relays;

    /**
     * Request message sent to relay.
     *
     * @var string
     */
    private string $payload;

    /**
     * Constructor for the Request class.
     * Initializes the url and payload properties based on the provided websocket and message.
     */
    public function __construct(Relay|RelaySet $relay, $message)
    {
        if ($relay instanceof RelaySet) {
            $this->relays = $relay;
        } else {
            // Create RelaySet with a single relay.
            $relaySet = new RelaySet();
            $relaySet->setRelays([$relay]);
            $this->relays = $relaySet;
        }
        $this->payload = $message->generate();
    }

    /**
     * @inheritDoc
     */
    public function send(): array
    {
        try {
            // TODO work out a nice solution with different RelayResponses.
            $result = [];
            // Send message to each relay defined in this set in $this->relays.
            /** @var Relay $relay */
            foreach ($this->relays->getRelays() as $relay) {
                $result[$relay->getUrl()] = $this->getResponseFromRelay($relay);
            }
        } catch (WebSocket\Exception\ClientException $e) {
            $result[$relay->getUrl()][] = [
                'ERROR',
                '',
                false,
                $e->getMessage(),
            ];
        }

        return $result;
    }

    /**
     * Method to send a request using WebSocket client, receive responses, and handle errors.
     *
     * @param Relay $relay
     * @return array
     */
    private function getResponseFromRelay(Relay $relay): array
    {
        $client = new WebSocket\Client($relay->getUrl());
        $client->text($this->payload);
        $result = [];
        /**
         * When sending 'CLOSE' request to close a subscription, it is not guaranteed that we
         * will receive a response confirming that the subscription with the given ID is closed
         * as the protocol does not mandate a specific response for a "CLOSE" request
         * We can handle this either by:
         *  - closing connection upon sending the request
         *  - waiting for a certain period to see if further events are received for that subscription ID
         *  - waiting for ping from server to close connection (in which case the server indicates the
         *    connection is still alive, but it does not confirm the closure of the subscription)
         */
        while ($response = $client->receive()) {
            if ($response instanceof WebSocket\Message\Ping) {
                $client->disconnect();
                return $result;
            }
            if ($response instanceof WebSocket\Message\Text) {
                $response = json_decode($response->getContent());
                if ($response[0] === 'NOTICE' || $response[0] === 'CLOSED') {
                    $client->disconnect();
                    throw new \RuntimeException($response[0] === 'NOTICE' ? $response[1] : $response[2]);
                }
                if ($response[0] === 'EOSE') {
                    break;
                }
                $result[] = $response;
            }
        }
        $client->disconnect();
        return $result;
    }
}
