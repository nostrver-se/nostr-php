<?php

declare(strict_types=1);

namespace swentel\nostr;

interface RequestInterface
{
    /**
     * Method to set the timeout in seconds for the websocket connection between the client and server.
     *
     * @param int|float $timeout
     *  Response timeout in seconds for the websocket connection.
     */
    public function setTimeout(int|float $timeout): static;

    /**
     * Method to send all data to the Websocket client which will connect to the relay(s).
     *
     * @return array
     */
    public function send(): array;
}
