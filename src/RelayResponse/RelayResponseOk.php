<?php

declare(strict_types=1);

namespace swentel\nostr\RelayResponse;

class RelayResponseOk extends RelayResponse
{
    public string $eventId;

    public bool $status;

    public string $message;

    public function __construct($response)
    {
        parent::__construct($response);
        $this->eventId = $response[1];
        $this->status = $response[2];
        $this->message = $response[3];
    }
}
