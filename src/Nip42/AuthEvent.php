<?php

declare(strict_types=1);

namespace swentel\nostr\Nip42;

use swentel\nostr\Event\Event;

/**
 * NIP-42: https://github.com/nostr-protocol/nips/blob/master/42.md
 * AuthEvent class for canonical authentication event.
 */
class AuthEvent extends Event
{
    /**
     * Event kind for canonical authentication event sent to the relay.
     *
     * @var int
     */
    protected int $kind = 22242;

    /**
     * Base constructor for AuthEvent.
     */
    public function __construct($relayUri, $challenge)
    {
        parent::__construct();
        $this->setTags([
            ['relay', $relayUri],
            ['challenge', $challenge],
        ]);
    }
}
