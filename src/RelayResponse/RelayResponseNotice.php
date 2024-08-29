<?php

declare(strict_types=1);

namespace swentel\nostr\RelayResponse;

class RelayResponseNotice extends RelayResponse
{
    public string $message;

    public function __construct($response)
    {
        parent::__construct($response);
        $this->message = $response[1];
    }
}
