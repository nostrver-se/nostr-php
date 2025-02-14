<?php

declare(strict_types=1);

require_once __DIR__ . '/../../vendor/autoload.php';

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

$charliePrivKey = $keyGenerator->generatePrivateKey();
$charliePubKey = $keyGenerator->getPublicKey($charliePrivKey);
$charlieNpub = $keyGenerator->convertPublicKeyToBech32($charliePubKey);

echo "Generated keys:\n";
echo "Alice's public key (npub): $aliceNpub\n";
echo "Bob's public key (npub): $bobNpub\n";
echo "Charlie's public key (npub): $charlieNpub\n\n";

// Example: NIP-44 Gift Wrapping
echo "NIP-44 Gift Wrapping Example:\n";
echo "---------------------------\n";

$message = "This is a secret message for both Bob and Charlie!";

// Generate a random conversation key for this message
$messageKey = random_bytes(32);

// Encrypt the actual message using the message key
$encryptedMessage = Nip44::encrypt($message, $messageKey);

// Create seals (encrypted message keys) for each recipient
$seals = [];

// Create Bob's seal
$bobConversationKey = Nip44::getConversationKey($alicePrivKey, $bobPubKey);
$bobSeal = Nip44::encrypt(bin2hex($messageKey), $bobConversationKey);
$seals[] = [
    'pubkey' => $bobPubKey,
    'seal' => $bobSeal,
];

// Create Charlie's seal
$charlieConversationKey = Nip44::getConversationKey($alicePrivKey, $charliePubKey);
$charlieSeal = Nip44::encrypt(bin2hex($messageKey), $charlieConversationKey);
$seals[] = [
    'pubkey' => $charliePubKey,
    'seal' => $charlieSeal,
];

// Create the event with the wrapped message
$event = new Event();
$event->setKind(44);
$event->setContent(json_encode([
    'content' => $encryptedMessage,
    'seals' => $seals,
]));

// Add recipient tags
foreach ($seals as $seal) {
    $event->addTag(['p', $seal['pubkey']]);
}

// Sign the event
$signer = new Sign();
$event->setCreatedAt(time());
$signer->signEvent($event, $alicePrivKey);

echo "Original message: $message\n";
echo "Gift-wrapped event content:\n";
echo $event->getContent() . "\n\n";

// Demonstrate decryption by recipients
$wrappedContent = json_decode($event->getContent(), true);

// Bob decrypts the message
echo "Bob's decryption:\n";
echo "-----------------\n";
$bobConversationKey = Nip44::getConversationKey($bobPrivKey, $alicePubKey);
foreach ($wrappedContent['seals'] as $seal) {
    if ($seal['pubkey'] === $bobPubKey) {
        // Decrypt the message key using Bob's conversation key
        $decryptedKey = Nip44::decrypt($seal['seal'], $bobConversationKey);
        // Use the decrypted key to decrypt the actual message
        $bobMessage = Nip44::decrypt($wrappedContent['content'], hex2bin($decryptedKey));
        echo "Decrypted by Bob: $bobMessage\n";
        break;
    }
}

// Charlie decrypts the message
echo "\nCharlie's decryption:\n";
echo "-------------------\n";
$charlieConversationKey = Nip44::getConversationKey($charliePrivKey, $alicePubKey);
foreach ($wrappedContent['seals'] as $seal) {
    if ($seal['pubkey'] === $charliePubKey) {
        // Decrypt the message key using Charlie's conversation key
        $decryptedKey = Nip44::decrypt($seal['seal'], $charlieConversationKey);
        // Use the decrypted key to decrypt the actual message
        $charlieMessage = Nip44::decrypt($wrappedContent['content'], hex2bin($decryptedKey));
        echo "Decrypted by Charlie: $charlieMessage\n";
        break;
    }
}

// Demonstrate that Eve cannot decrypt the message
echo "\nEve's attempt:\n";
echo "-------------\n";
$evePrivKey = $keyGenerator->generatePrivateKey();
$evePubKey = $keyGenerator->getPublicKey($evePrivKey);
$eveNpub = $keyGenerator->convertPublicKeyToBech32($evePubKey);
echo "Eve's public key (npub): $eveNpub\n";

try {
    $eveConversationKey = Nip44::getConversationKey($evePrivKey, $alicePubKey);
    foreach ($wrappedContent['seals'] as $seal) {
        if ($seal['pubkey'] === $evePubKey) {
            $decryptedKey = Nip44::decrypt($seal['seal'], $eveConversationKey);
            $eveMessage = Nip44::decrypt($wrappedContent['content'], hex2bin($decryptedKey));
            echo "Eve somehow decrypted: $eveMessage\n";
            break;
        }
    }
} catch (Exception $e) {
    echo "Eve failed to decrypt (as expected): No seal found for Eve's key\n";
}
