<?php

declare(strict_types=1);

namespace swentel\nostr;

interface RelayInterface
{
    /**
     * Get url of the relay.
     *
     * @return string
     */
    public function getUrl(): string;

    /**
     * Send the message to the relay.
     *
     * @return CommandResultInterface
     */
    public function send(): CommandResultInterface;
}
