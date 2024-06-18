<?php

declare(strict_types=1);

namespace swentel\nostr\Request;

use swentel\nostr\RequestInterface;
use WebSocket;

class Request implements RequestInterface
{
    /**
     * Repay url
     */
    private string $url;

    /**
     * Request message sent to relay
     */
    private string $payload;

    /**
     * Constructor for the Request class.
     * Initializes the url and payload properties based on the provided websocket and message.
     */
    public function __construct(string $websocket, $message)
    {
        $this->url = $websocket;
        $this->payload = $message->generate();
    }

    /**
     * Method to send a request using WebSocket client, receive responses, and handle errors.
     *
     * @return array The array of responses received or an error message if connection fails.
     */
    public function send(): array
    {
        try {
            $client = new WebSocket\Client($this->url);
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
                } elseif ($response instanceof WebSocket\Message\Text) {
                    $response = json_decode($response->getContent());
                    if ($response[0] === 'NOTICE' || $response[0] === 'CLOSED') {
                        $client->disconnect();
                        throw new \RuntimeException($response[0] === 'NOTICE' ? $response[1] : $response[2]);
                    } elseif ($response[0] === 'EOSE') {
                        break;
                    } else {
                        $result[] = $response;
                    }
                }
            }
            $client->disconnect();
        } catch (WebSocket\ConnectionException $e) {
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
