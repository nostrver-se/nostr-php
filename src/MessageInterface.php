<?php

declare(strict_types=1);

namespace swentel\nostr;

interface MessageInterface
{
    /**
     * Generate the message ready to be sent to a relay.
     *
     * @return string
     */
    public function generate(): string;
}
