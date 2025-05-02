<?php

declare(strict_types=1);

namespace swentel\nostr\Request;

use swentel\nostr\Event\Event;
use swentel\nostr\Message\AuthMessage;
use swentel\nostr\MessageInterface;
use swentel\nostr\Nip42\AuthEvent;
use swentel\nostr\Relay\Relay;
use swentel\nostr\Relay\RelaySet;
use swentel\nostr\RelayResponse\RelayResponse;
use swentel\nostr\RequestInterface;
use swentel\nostr\Sign\Sign;
use WebSocket;
use WebSocket\Client;
use WebSocket\Connection;
use WebSocket\Message\Pong;
use WebSocket\Message\Text;

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
     * Array with all responses received from the relay.
     *
     * @var array
     */
    protected array $responses;

    /**
     * Constructor for the Request class.
     * Initializes the url and payload properties based on the provided websocket and message.
     */
    public function __construct(Relay|RelaySet $relay, MessageInterface $message)
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
        $result = [];
        // Send message to each relay defined in this set in $this->relays.
        /** @var Relay $relay */
        foreach ($this->relays->getRelays() as $relay) {
            try {
                $result[$relay->getUrl()] = $this->getResponseFromRelay($relay);
            } catch (WebSocket\Exception\Exception $e) {
                $result[$relay->getUrl()][] = [
                    'ERROR',
                    '',
                    false,
                    $e->getMessage(),
                ];
            }
        }

        return $result;
    }

    /**
     * Method to send a request using WebSocket client, receive responses, and handle errors.
     *
     * @param Relay $relay
     * @return array|RelayResponse
     * @throws \Throwable
     */
    private function getResponseFromRelay(Relay $relay): array | RelayResponse
    {
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

        $client = $relay->getClient();
        $client->setTimeout(60);

        try {
            $client->text($this->payload);
        } catch (\Exception $e) {
            throw $e;
        }
        // The Nostr subscription lifecycle within a websocket connection lifecycle.
        while ($response = $client->receive()) {
            if ($response === null) {
                $response = [
                    'ERROR',
                    'Invalid response',
                ];
                $client->disconnect();
                return RelayResponse::create($response);
            } elseif ($response instanceof WebSocket\Message\Ping) {
                // Send pong message.
                $pongMessage = new Pong();
                $client->text($pongMessage->getPayload());
            } elseif ($response instanceof Text) {
                $relayResponse = RelayResponse::create(json_decode($response->getContent()));
                $this->responses[] = $relayResponse;
                // NIP-01 - Response OK from the relay.
                if ($relayResponse->type === 'OK' && $relayResponse->status === false) {
                    // Something went wrong, see message from the relay why.
                    $client->disconnect();
                    throw new \Exception($relayResponse->message);
                }
                if ($relayResponse->type === 'OK' && $relayResponse->status === true) {
                    if (isset($relayResponse->eventId) && $relayResponse->eventId !== '') {
                        // Event is transmitted to the relay.
                        // TODO: send closeMessage to relay.
                        $client->disconnect();
                        break;
                    }
                    if (str_starts_with($relayResponse->message, 'auth-required:')) {
                        // NIP-42
                        // Auth required
                        if (!isset($_SESSION['challenge'])) {
                            $client->disconnect();
                            $message = sprintf(
                                'Relay %s requires auth and there is no challenge set in $_SESSION. Did we get an AUTH response first?',
                                $relay->getUrl(),
                            );
                            throw new \Exception($message);
                        }
                        // TODO: send AUTH message to the relay here
                    }
                    if (str_starts_with($relayResponse->message, 'restricted:')) {
                        // For when a client has already performed AUTH but the key used to perform
                        // it is still not allowed by the relay or is exceeding its authorization.
                        $client->disconnect();
                        throw new \Exception($relayResponse->message);
                    }
                }
                // NIP-01 - Response EVENT from the relay.
                if ($relayResponse->type === 'EVENT') {
                    // Do nothing.
                }
                // NIP-01 - Response EOSE from the relay.
                if ($relayResponse->type === 'EOSE') {
                    // We should send closeMessage to the relay here.
                    $client->disconnect();
                    break;
                }
                if ($relayResponse->type === 'OK' && $relayResponse->status === false) {
                    // We should send closeMessage to the relay here.
                    $client->disconnect();
                    throw new \Exception($relayResponse->message);
                }
                // NIP-42 - Response AUTH from the relay.
                if ($relayResponse->type === 'AUTH') {
                    // Save challenge string in session.
                    $_SESSION['challenge'] = $relayResponse->message;
                }
                // NIP-01 - Response CLOSED from the relay.
                if ($relayResponse->type === 'CLOSED') {
                    // NIP-42
                    // We do need to broadcast a signed event verification here to the relay.
                    if (str_starts_with($relayResponse->message, 'auth-required:')) {
                        if (!isset($_SESSION['challenge'])) {
                            $client->disconnect();
                            $message = sprintf(
                                'Relay %s requires auth and there is no challenge set in $_SESSION. Did we get an AUTH response first?',
                                $relay->getUrl(),
                            );
                            throw new \Exception($message);
                        }
                        $authEvent = new AuthEvent($relay->getUrl(), $_SESSION['challenge']);
                        $sec = '0000000000000000000000000000000000000000000000000000000000000001';
                        // todo: use client defined secret key here instead of this default one
                        $signer = new Sign();
                        $signer->signEvent($authEvent, $sec);
                        $authMessage = new AuthMessage($authEvent);
                        $initialMessage = $this->payload;
                        $this->payload = $authMessage->generate();
                        $client->text($this->payload);
                        // Set listener.
                        $client->onText(function (Client $client, Connection $connection, Text $message) {
                            $this->responses[] = RelayResponse::create(json_decode($message->getContent()));
                            $client->stop();
                        })->start();
                        // Broadcast the initial message to the relay now the AUTH is done.
                        $this->payload = $initialMessage;
                        $client->text($this->payload);
                        $client->onText(function (Client $client, Connection $connection, Text $message) {
                            /** @var RelayResponse $response */
                            $response = RelayResponse::create(json_decode($message->getContent()));
                            $this->responses[] = $response;
                            $client->stop();
                            if ($response->type === 'EOSE') {
                                // We should send closeMessage to the relay here.
                                $client->disconnect();
                            }
                        })->start();
                        break;
                    }
                    if (str_starts_with($relayResponse->message, 'restricted:')) {
                        // For when a client has already performed AUTH but the key used to perform
                        // it is still not allowed by the relay or is exceeding its authorization.
                        $client->disconnect();
                        throw new \Exception($relayResponse->message);
                    }
                    $client->disconnect();
                    break;
                }
            }
        }
        if ($client->isConnected()) {
            $client->disconnect();
        }
        $client->close();
        return $this->responses;
    }
}
