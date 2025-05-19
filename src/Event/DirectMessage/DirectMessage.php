<?php

declare(strict_types=1);

namespace swentel\nostr\Event\DirectMessage;

use swentel\nostr\Event\Event;

class DirectMessage extends Event
{
    /**
     * Construct a Direct Message event (kind 14)
     */
    public function __construct()
    {
        parent::__construct();
        $this->setKind(14);
    }

    /**
     * Add a recipient to the direct message
     *
     * @param string $pubkey The recipient's public key
     * @param string|null $relayUrl Optional relay URL for the recipient
     * @return self
     */
    public function addRecipient(string $pubkey, ?string $relayUrl = null): self
    {
        $tag = ['p', $pubkey];
        if ($relayUrl !== null) {
            $tag[] = $relayUrl;
        }

        $this->addTag($tag);
        return $this;
    }

    /**
     * Set the message as a reply to another message
     *
     * @param string $eventId The ID of the message being replied to
     * @param string|null $relayUrl Optional relay URL where the original message can be found
     * @return self
     */
    public function setAsReplyTo(string $eventId, ?string $relayUrl = null): self
    {
        $tag = ['e', $eventId];
        if ($relayUrl !== null) {
            $tag[] = $relayUrl;
        }

        $this->addTag($tag);
        return $this;
    }

    /**
     * Set the sender's public key
     *
     * @param string $pubkey The sender's public key
     * @return self
     */
    public function setSenderPubkey(string $pubkey): self
    {
        $this->pubkey = $pubkey;
        return $this;
    }
}
