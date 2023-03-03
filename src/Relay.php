<?php

namespace swentel\nostr;

use WebSocket;

class Relay
{
    private $url;

    /**
     * Initiate relay.
     *
     */
    function __construct($url)
    {
        //  tbd: check if url is valid / connection can be established ?
        $this->url = $url;
    }

    /**
     * Publish event to relay.
     *
     * @param String $event
     *
     * @return Array
     */
    public function publish($event)
    {
        $client = new WebSocket\Client($this->url);
        $client->text($event);
        $response = $client->receive();
        $client->close();

        return json_decode($response);
    }
}
