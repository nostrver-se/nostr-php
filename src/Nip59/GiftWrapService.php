<?php

declare(strict_types=1);

namespace swentel\nostr\Nip59;

use swentel\nostr\EventInterface;
use swentel\nostr\Key\Key;
use swentel\nostr\Encryption\Nip44;
use swentel\nostr\Sign\Sign;

/**
 * NIP-59: https://github.com/nostr-protocol/nips/blob/master/59.md
 * GiftWrapService class for creating gift wraps.
 */
class GiftWrapService
{
    private Key $keyService;
    private Sign $signService;

    public function __construct(Key $keyService, Sign $signService)
    {
        $this->keyService = $keyService;
        $this->signService = $signService;
    }

    /**
     * Create a seal for an event
     *
     * @param EventInterface $event The event to seal
     * @param string $senderPrivkey Sender's private key
     * @param string $recipientPubkey Recipient's public key
     * @return EventInterface The sealed event
     */
    public function createSeal(EventInterface $event, string $senderPrivkey, string $recipientPubkey): EventInterface
    {
        $serializedEvent = json_encode($event->toArray());

        // Get conversation key using the existing Nip44 implementation
        $conversationKey = Nip44::getConversationKey($senderPrivkey, $recipientPubkey);

        // Encrypt the event using the existing Nip44 implementation
        $encryptedContent = Nip44::encrypt($serializedEvent, $conversationKey);

        $sealEvent = new \swentel\nostr\Event\Event();
        $sealEvent->setKind(13); // Seal kind
        $sealEvent->setContent($encryptedContent);
        $sealEvent->setCreatedAt(time());

        $this->signService->signEvent($sealEvent, $senderPrivkey);
        return $sealEvent;
    }

    /**
     * Create a complete gift wrap as specified in NIP-59
     *
     * @param EventInterface $event The event to gift wrap
     * @param string $senderPrivkey Sender's private key
     * @param string $recipientPubkey Recipient's public key
     * @return GiftWrapInterface The gift wrapped event
     */
    public function createGiftWrap(
        EventInterface $event,
        string $recipientPubkey,
    ): GiftWrapInterface {
        // Generate a random one-time-use private key as required by NIP-59
        $randomPrivkey = bin2hex(random_bytes(32));
        $randomPubkey = $this->keyService->getPublicKey($randomPrivkey);

        // Create the gift wrap event (kind 1059)
        $giftWrap = new GiftWrap();
        $giftWrap->setKind(1059);
        $giftWrap->setCreatedAt(time());
        $giftWrap->setPublicKey($randomPubkey); // Set the random pubkey
        $giftWrap->addRecipient($recipientPubkey); // Add recipient's p tag

        // Serialize and encrypt the seal for the gift wrap
        $serializedSeal = json_encode($event->toArray());
        $wrapConversationKey = Nip44::getConversationKey($randomPrivkey, $recipientPubkey);
        $encryptedSeal = Nip44::encrypt($serializedSeal, $wrapConversationKey);


        $giftWrap->setEncryptedContent($encryptedSeal);

        // Sign the gift wrap with the random one-time-use key
        $this->signService->signEvent($giftWrap, $randomPrivkey);

        return $giftWrap;
    }
}
