<?php

namespace swentel\nostr;

use WebSocket;

class Relay implements RelayInterface
{
    private $url;

    /**
     * Initiate relay.
     *
     */
    function __construct($url)
    {
        //  tbd: valid url
        $this->url = $url;
    }

    /**
     * Publish message to relay.
     *
     * @param String $message
     *
     * @return Array
     */
    public function publish($message)
    {
        $client = new WebSocket\Client($this->url);
        $client->text($message);
        $response = $client->receive();
        $client->close();

        return new RelayResponse($response);
    }
}
