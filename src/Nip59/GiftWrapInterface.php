<?php

declare(strict_types=1);

namespace swentel\nostr\Nip59;

use swentel\nostr\EventInterface;

interface GiftWrapInterface extends EventInterface
{
    /**
     * Add a recipient pubkey to this gift wrap
     *
     * @param string $pubkey The recipient's public key
     * @return self
     */
    public function addRecipient(string $pubkey): self;

    /**
     * Get the recipient pubkey
     *
     * @return string|null The recipient's public key
     */
    public function getRecipient(): ?string;

    /**
     * Set the encrypted content
     *
     * @param string $encryptedContent The encrypted event
     * @return self
     */
    public function setEncryptedContent(string $encryptedContent): self;

    /**
     * Get the encrypted content
     *
     * @return string The encrypted content
     */
    public function getEncryptedContent(): string;
}
