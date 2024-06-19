<?php

declare(strict_types=1);

namespace swentel\nostr;

interface RequestInterface
{
    /**
     * Method to send all data to the Websocket client which will connect to the relay(s).
     *
     * @return array
     */
    public function send(): array;
}
