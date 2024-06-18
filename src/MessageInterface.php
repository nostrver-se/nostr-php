<?php

declare(strict_types=1);

namespace swentel\nostr;

use swentel\nostr\Message\MessageTypeEnum;

interface MessageInterface
{
    /**
     * @param MessageTypeEnum $type
     * @return void
     */
    public function setType(MessageTypeEnum $type): void;

    /**
     * Generate the message ready to be sent to a relay.
     *
     * @return string
     */
    public function generate(): string;
}
