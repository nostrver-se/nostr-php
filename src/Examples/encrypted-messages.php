<?php

declare(strict_types=1);

require_once __DIR__ . '/../../vendor/autoload.php';

use swentel\nostr\Encryption\Nip04;
use swentel\nostr\Encryption\Nip44;
use swentel\nostr\Event\Event;
use swentel\nostr\Sign\Sign;
use swentel\nostr\Key\Key;

// Initialize key generator
$keyGenerator = new Key();

// Generate keys for our participants
$alicePrivKey = $keyGenerator->generatePrivateKey();
$alicePubKey = $keyGenerator->getPublicKey($alicePrivKey);
$aliceNpub = $keyGenerator->convertPublicKeyToBech32($alicePubKey);

$bobPrivKey = $keyGenerator->generatePrivateKey();
$bobPubKey = $keyGenerator->getPublicKey($bobPrivKey);
$bobNpub = $keyGenerator->convertPublicKeyToBech32($bobPubKey);

echo "Generated keys:\n";
echo "Alice's public key (npub): $aliceNpub\n";
echo "Bob's public key (npub): $bobNpub\n\n";

// Example 1: NIP-04 Direct Message
echo "NIP-04 Example (Direct Message):\n";
echo "--------------------------------\n";

$message = "Hello Bob, this is a secret message using NIP-04!";

// Create and encrypt the message
$event = new Event();
$event->setKind(4); // kind 4 = encrypted direct message
$event->setContent(Nip04::encrypt($message, $alicePrivKey, $bobPubKey));
$event->addTag(['p', $bobPubKey]); // tag the recipient

// Sign the event
$signer = new Sign();
$event->setCreatedAt(time());
$signer->signEvent($event, $alicePrivKey);

echo "Original message: $message\n";
echo "Encrypted event content: " . $event->getContent() . "\n";

// Bob decrypts the message
$decrypted = Nip04::decrypt($event->getContent(), $bobPrivKey, $alicePubKey);
echo "Decrypted by Bob: $decrypted\n\n";

// Example 2: NIP-44 Encrypted Message
echo "NIP-44 Example (Modern Encryption):\n";
echo "---------------------------------\n";

$message = "Hello Bob, this is a secret message using NIP-44!";

// Get conversation key
$conversationKey = Nip44::getConversationKey($alicePrivKey, $bobPubKey);

// Create and encrypt the message
$event = new Event();
$event->setKind(44); // kind 44 = NIP-44 encrypted message
$event->setContent(Nip44::encrypt($message, $conversationKey));
$event->addTag(['p', $bobPubKey]); // tag the recipient

// Sign the event
$event->setCreatedAt(time());
$signer->signEvent($event, $alicePrivKey);

echo "Original message: $message\n";
echo "Encrypted event content: " . $event->getContent() . "\n";

// Bob gets the same conversation key and decrypts
$bobConversationKey = Nip44::getConversationKey($bobPrivKey, $alicePubKey);
$decrypted = Nip44::decrypt($event->getContent(), $bobConversationKey);
echo "Decrypted by Bob: $decrypted\n\n";

// Demonstrate that both keys derive the same conversation key
echo "Conversation key verification:\n";
echo "Alice's derived key: " . bin2hex($conversationKey) . "\n";
echo "Bob's derived key:   " . bin2hex($bobConversationKey) . "\n";
echo "Keys match: " . ($conversationKey === $bobConversationKey ? "Yes" : "No") . "\n";
