<?php

declare(strict_types=1);

namespace swentel\nostr\Nip17;

interface DirectMessageInterface
{
    /**
     * Send a direct message to a recipient and create a copy for the sender
     *
     * @param string $senderPrivkey The sender's private key
     * @param string $receiverPubkey The receiver's public key
     * @param string $message The message content
     * @param array $additionalTags Additional tags to include
     * @param string|null $replyToId ID of message being replied to (optional)
     *
     * @return array An array containing both GiftWraps (receiver and sender copies)
     */
    public function sendDirectMessage(
        string $senderPrivkey,
        string $receiverPubkey,
        string $message,
        array $additionalTags = [],
        ?string $replyToId = null,
    ): array;

    /**
     * Check if a pubkey has published a kind 10050 event with preferred relays
     *
     * @param string $pubkey The public key to check
     * @return bool True if the pubkey has published a kind 10050 event
     */
    //    public function hasPublishedRelayList(string $pubkey): bool;

    /**
     * Get preferred relays for a pubkey from kind 10050 events
     *
     * @param string $pubkey The public key to get relays for
     * @return array Array of relay URLs
     */
    //    public function getPreferredRelaysForPubkey(string $pubkey): array;
}
