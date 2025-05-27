<?php

declare(strict_types=1);

namespace swentel\nostr\Nip17;

use swentel\nostr\Encryption\Nip44;
use swentel\nostr\Event\DirectMessage\DirectMessage as DirectMessageEvent;
use swentel\nostr\Event\List\DmRelaysList;
use swentel\nostr\EventInterface;
use swentel\nostr\Key\Key;
use swentel\nostr\Nip59\GiftWrapInterface;
use swentel\nostr\Nip59\GiftWrapService;

/**
 * NIP-17: https://github.com/nostr-protocol/nips/blob/master/17.md
 * DirectMessage class for sending direct messages.
 */
class DirectMessage implements DirectMessageInterface
{
    private GiftWrapService $giftWrapService;
    private Key $keyService;

    public function __construct(
        GiftWrapService $giftWrapService,
        Key $keyService,
    ) {
        $this->giftWrapService = $giftWrapService;
        $this->keyService = $keyService;
    }

    /**
     * Send a direct message using both seal and gift wrap for maximum privacy.
     *
     * @param string $senderPrivkey The sender's private key
     * @param string $receiverPubkey The receiver's public key
     * @param string $message The message content
     * @param array $additionalTags Additional tags to include
     * @param string|null $replyToId ID of message being replied to (optional)
     *
     * @return array An array containing the created seal, gift wrap, and relay information
     */
    public function sendDirectMessage(
        string $senderPrivkey,
        string $receiverPubkey,
        string $message,
        array $additionalTags = [],
        ?string $replyToId = null,
    ): array {
        // Derive sender's public key from private key
        $senderPubkey = $this->keyService->getPublicKey($senderPrivkey);

        // Discover receiver's preferred relays
        $receiverRelays = new DmRelaysList();
        $receiverRelays->getRelays($receiverPubkey);

        // Get sender's preferred relays
        $senderRelays = new DmRelaysList();
        $senderRelays->getRelays($receiverPubkey);

        // Create the base event (kind 14)
        $event = $this->createDirectMessageEvent($message, $receiverPubkey, $replyToId, $additionalTags);
        $event->setSenderPubkey($senderPubkey);

        // // Step 1: Create a seal (kind 13) for the event
        $sealEvent = $this->giftWrapService->createSeal($event, $senderPrivkey, $receiverPubkey);

        // // Step 2: Create a gift wrap (kind 1059) to further protect the seal
        $receiverGiftWrap = $this->giftWrapService->createGiftWrap($sealEvent, $receiverPubkey);

        // Also create a copy for the sender to keep track of sent messages
        $senderGiftWrap = $this->giftWrapService->createGiftWrap($sealEvent, $senderPubkey);

        return [
            'seal' => $sealEvent,
            'receiver' => $receiverGiftWrap,
            'sender' => $senderGiftWrap,
            'receiver_relays' => $receiverRelays,
            'sender_relays' => $senderRelays,
        ];
    }

    /**
     * Decrypt a direct message from a gift wrap and seal.
     *
     * @param GiftWrapInterface|EventInterface|\stdClass $giftWrap The gift wrapped event to decrypt
     * @param string $receiverPrivkey The private key of the recipient (you)
     * @param bool $verifyRecipient Whether to verify that the gift wrap is addressed to the receiver
     *
     * @return array|null The decrypted event as an array, or null if decryption failed or recipient verification failed
     * @throws \Exception
     */
    public static function decryptDirectMessage(
        $giftWrap,
        string $receiverPrivkey,
        bool $verifyRecipient = true,
    ): ?array {
        // Extract the recipient pubkey from p tag
        $receiverPubkey = (new Key())->getPublicKey($receiverPrivkey);
        $isAddressedToReceiver = false;

        // Check that the gift wrap is addressed to the receiver via p tag
        // Handle both Event objects and stdClass objects from relays
        $tags = method_exists($giftWrap, 'getTags') ? $giftWrap->getTags() : ($giftWrap->tags ?? []);

        foreach ($tags as $tag) {
            if ($tag[0] === 'p' && $tag[1] === $receiverPubkey) {
                $isAddressedToReceiver = true;
                break;
            }
        }

        // If verifying recipient and the gift wrap is not for this receiver, abort
        if ($verifyRecipient && !$isAddressedToReceiver) {
            throw new \Exception("Gift wrap is not addressed to the receiver");
        }

        try {
            // Extract the encrypted content from the gift wrap
            $encryptedContent = method_exists($giftWrap, 'getContent') ?
                $giftWrap->getContent() :
                ($giftWrap->content ?? '');

            // Extract the gift wrap's random public key
            $giftWrapPubkey = method_exists($giftWrap, 'getPublicKey') ?
                $giftWrap->getPublicKey() :
                $giftWrap->pubkey;

            // Create the conversation key for the gift wrap layer
            // Gift wrap is encrypted using a conversation key between the recipient and the one-time random pubkey
            $giftWrapConversationKey = Nip44::getConversationKey($receiverPrivkey, $giftWrapPubkey);

            // Step 1: Decrypt the gift wrap to get the seal
            $decryptedSeal = Nip44::decrypt($encryptedContent, $giftWrapConversationKey);
            $sealData = json_decode($decryptedSeal, true);


            if (!isset($sealData['pubkey'])) {
                throw new \Exception("No pubkey found in seal");
            }

            // Step 2: Decrypt the seal to get the original event
            if (isset($sealData['content'])) {
                // The seal is encrypted using the conversation key with the sender's pubkey
                $sealConversationKey = Nip44::getConversationKey($receiverPrivkey, $sealData['pubkey']);

                $decryptedContent = Nip44::decrypt($sealData['content'], $sealConversationKey);

                // Parse the decrypted content (which is a serialized event)
                return json_decode($decryptedContent, true);
            }

            throw new \Exception("No content found in seal");
        } catch (\Exception $e) {
            // Decryption failed
            print "Decryption failed: " . $e->getMessage() . PHP_EOL;
            throw $e;
        }
    }

    /**
     * Create a direct message event (kind 14)
     *
     * @param string $message The message content
     * @param string $receiverPubkey The receiver's public key
     * @param string|null $replyToId ID of message being replied to (optional)
     * @param array $additionalTags Additional tags to include
     *
     * @return EventInterface
     */
    private function createDirectMessageEvent(
        string $message,
        string $receiverPubkey,
        ?string $replyToId = null,
        array $additionalTags = [],
    ): EventInterface {
        $event = new DirectMessageEvent();
        $event->setContent($message);
        $event->addRecipient($receiverPubkey);

        if ($replyToId) {
            $event->setAsReplyTo($replyToId);
        }

        foreach ($additionalTags as $tag) {
            $event->addTag($tag);
        }

        return $event;
    }
}
