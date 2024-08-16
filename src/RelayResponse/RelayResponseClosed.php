<?php

declare(strict_types=1);

namespace swentel\nostr\RelayResponse;

class RelayResponseClosed extends RelayResponse
{
    public string $subscriptionId;

    public string $message;

    public function __construct($response)
    {
        parent::__construct($response);
        $this->subscriptionId = $response[1];
        $this->message = $response[2];
    }
}
