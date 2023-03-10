<?php

namespace swentel\nostr;

interface RelayInterface {

    /**
     * Send the message to the relay.
     *
     * @return CommandResultInterface
     */
    public function send(): CommandResultInterface;

}
