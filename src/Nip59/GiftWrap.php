<?php

declare(strict_types=1);

namespace swentel\nostr\Nip59;

use swentel\nostr\Event\Event;

/**
 * NIP-59: https://github.com/nostr-protocol/nips/blob/master/59.md
 * GiftWrap class for gift wrapping messages.
 */
class GiftWrap extends Event implements GiftWrapInterface
{
    /**
     * Construct a GiftWrap event (kind 1059)
     */
    public function __construct()
    {
        parent::__construct();
        $this->setKind(1059);
    }

    /**
     * {@inheritdoc}
     */
    public function addRecipient(string $pubkey): self
    {
        $this->addTag(['p', $pubkey]);
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getRecipient(): ?string
    {
        foreach ($this->getTags() as $tag) {
            if ($tag[0] === 'p' && isset($tag[1])) {
                return $tag[1];
            }
        }

        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function setEncryptedContent(string $encryptedContent): self
    {
        $this->setContent($encryptedContent);
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getEncryptedContent(): string
    {
        return $this->getContent();
    }
}
