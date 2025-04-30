<?php

declare(strict_types=1);

use swentel\nostr\Key\Key;
use swentel\nostr\Nip17\DirectMessage;
use swentel\nostr\Nip59\GiftWrapService;
use swentel\nostr\Sign\Sign;

require __DIR__ . '/../../vendor/autoload.php';

// Set up keys for Alice and Bob
$key = new Key();
$sign = new Sign();
$giftWrapService = new GiftWrapService($key, $sign);

try {
    // Alice's private and public keys
    $alicePrivKey = '0123456789abcdef0123456789abcdef0123456789abcdef0123456789abcdef';
    $alicePubKey = $key->getPublicKey($alicePrivKey);
    print "Alice's public key: " . $alicePubKey . PHP_EOL;

    // Bob's private and public keys
    $bobPrivKey = 'fedcba9876543210fedcba9876543210fedcba9876543210fedcba9876543210';
    $bobPubKey = $key->getPublicKey($bobPrivKey);
    print "Bob's public key: " . $bobPubKey . PHP_EOL;

    // Create DirectMessage service
    $directMessage = new DirectMessage($giftWrapService, $key);

    print PHP_EOL . "===== SENDING A PRIVATE DIRECT MESSAGE =====" . PHP_EOL;

    // Message from Alice to Bob
    $messageText = "Hey Bob, this is a private message that only you can read!";
    print "Alice is sending message to Bob: '" . $messageText . "'" . PHP_EOL;

    // Send the direct message, creating gift wraps for both sender and receiver
    $result = $directMessage->sendDirectMessage(
        $alicePrivKey,
        $bobPubKey,
        $messageText,
    );

    // Display information about the created gift wraps
    print PHP_EOL . "Two gift wraps were created:" . PHP_EOL;
    print "1. Receiver gift wrap (for Bob):" . PHP_EOL;
    print "   - Kind: " . $result['receiver']->getKind() . PHP_EOL;
    print "   - ID: " . $result['receiver']->getId() . PHP_EOL;

    print PHP_EOL . "2. Sender gift wrap (for Alice):" . PHP_EOL;
    print "   - Kind: " . $result['sender']->getKind() . PHP_EOL;
    print "   - ID: " . $result['sender']->getId() . PHP_EOL;

    print PHP_EOL . "===== PUBLISHING GIFT WRAPS TO RELAYS =====" . PHP_EOL;

    // Normally, you would get the relay list from the kind 10050 event (see NIP-51)
    // but for this example, we'll use hardcoded relay URLs
    print "Publishing to receiver's relay: wss://relay.example.com" . PHP_EOL;

    // In a real implementation, you would publish to relays from the result
    // $receiverRelays = $result['receiver_relays'];
    // $senderRelays = $result['sender_relays'];

    print PHP_EOL . "===== SIMULATING GIFT WRAP RECEPTION AND DECRYPTION =====" . PHP_EOL;

    // Simulate Bob receiving the gift wrap
    print "Bob received a gift wrap with ID: " . $result['receiver']->getId() . PHP_EOL;

    // In a real scenario, Bob would look for gift wraps addressed to him (p tag with his pubkey)
    // For this example, we'll just use the gift wrap we already created

    // Using the new static decryptDirectMessage helper method
    print "Bob is decrypting the message using his private key and Alice's public key..." . PHP_EOL;
    $decryptedEvent = DirectMessage::decryptDirectMessage(
        $result['receiver'],  // The gift wrap to decrypt
        $bobPrivKey,          // Bob's private key
        true,                 // Verify the gift wrap is addressed to Bob
    );

    if ($decryptedEvent) {
        print "Decryption successful!" . PHP_EOL;

        print PHP_EOL . "===== DECRYPTED MESSAGE DETAILS =====" . PHP_EOL;
        print "Message kind: " . $decryptedEvent['kind'] . PHP_EOL;
        print "Message content: '" . $decryptedEvent['content'] . "'" . PHP_EOL;

        // Get the p tags to identify the participants
        $tags = $decryptedEvent['tags'] ?? [];
        $participants = [];

        foreach ($tags as $tag) {
            if ($tag[0] === 'p') {
                $participants[] = $tag[1];
            }
        }

        print "Participants: " . implode(', ', $participants) . PHP_EOL;

        // Bob can now reply to Alice using the same process
        print PHP_EOL . "===== BOB REPLYING TO ALICE =====" . PHP_EOL;

        // Create a reply message
        $replyText = "Hey Alice, thanks for your message! This is a private reply.";
        print "Bob is sending reply to Alice: '" . $replyText . "'" . PHP_EOL;

        // Use the ID of the original message for reply
        $originalMessageId = $decryptedEvent['id'] ?? null;

        // Send the reply as a direct message
        $replyResult = $directMessage->sendDirectMessage(
            $bobPrivKey,
            $alicePubKey,
            $replyText,
            [],
            $originalMessageId,  // Set as reply to the original message
        );

        print PHP_EOL . "Reply gift wrap created with ID: " . $replyResult['receiver']->getId() . PHP_EOL;

        // Alice can now decrypt Bob's reply
        print PHP_EOL . "===== ALICE DECRYPTING BOB'S REPLY =====" . PHP_EOL;
        print "Alice received a gift wrap with ID: " . $replyResult['receiver']->getId() . PHP_EOL;

        $decryptedReply = DirectMessage::decryptDirectMessage(
            $replyResult['receiver'],  // The gift wrap to decrypt
            $alicePrivKey,             // Alice's private key
            true,                      // Verify the gift wrap is addressed to Alice
        );

        if ($decryptedReply) {
            print "Alice successfully decrypted Bob's reply!" . PHP_EOL;
            print "Reply content: '" . $decryptedReply['content'] . "'" . PHP_EOL;

            // Check if it's a reply to the original message
            $isReply = false;
            foreach ($decryptedReply['tags'] as $tag) {
                if ($tag[0] === 'e' && isset($tag[1]) && $tag[1] === $decryptedEvent['id']) {
                    $isReply = true;
                    break;
                }
            }

            if ($isReply) {
                print "This is a reply to Alice's original message." . PHP_EOL;
            }
        } else {
            print "Alice could not decrypt the message." . PHP_EOL;
        }
    } else {
        print "Decryption failed! The message might not be for Bob or the encryption keys don't match." . PHP_EOL;
    }

} catch (Exception $e) {
    print 'Exception: ' . $e->getMessage() . PHP_EOL;
}
