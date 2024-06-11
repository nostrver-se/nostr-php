<?php

declare(strict_types=1);

namespace swentel\nostr;

interface RequestInterface
{
    /**
     * Send the request to the relay.
     *
     * @return CommandResultInterface
     */
    public function send(): array;
}
