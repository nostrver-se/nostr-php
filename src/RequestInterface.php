<?php

declare(strict_types=1);

namespace swentel\nostr;

interface RequestInterface
{
    /**
     * Send the request to the relay.
     *
     * @return array
     */
    public function send(): array;
}
