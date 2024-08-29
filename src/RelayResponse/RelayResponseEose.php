<?php

declare(strict_types=1);

namespace swentel\nostr\RelayResponse;

class RelayResponseEose extends RelayResponse
{
    public string $subscriptionId;

    public function __construct($response)
    {
        parent::__construct($response);
        $this->subscriptionId = $response[1];
    }
}
