<?php

declare(strict_types=1);

namespace swentel\nostr\RelayResponse;

class RelayResponseEvent extends RelayResponse
{
    public string $subscriptionId;

    public \stdClass $event;

    public function __construct($response)
    {
        parent::__construct($response);
        $this->subscriptionId = $response[1];
        $this->event = $response[2];
    }
}
