<?php

declare(strict_types=1);

require_once __DIR__ . '/../../vendor/autoload.php';

use swentel\nostr\Encryption\Nip44;
use swentel\nostr\Event\Event;
use swentel\nostr\Sign\Sign;
use swentel\nostr\Key\Key;

try {
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

    echo "Generated keys:" . PHP_EOL;
    echo "Alice's public key (npub): $aliceNpub" . PHP_EOL;
    echo "Bob's public key (npub): $bobNpub" . PHP_EOL;
    echo "Charlie's public key (npub): $charlieNpub" . PHP_EOL . PHP_EOL;

    // Example: NIP-44 Gift Wrapping
    echo "NIP-44 Gift Wrapping Example:" . PHP_EOL;
    echo "---------------------------" . PHP_EOL;

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
    $event->setKind(4);
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

    echo "Original message: $message" . PHP_EOL;
    echo "Gift-wrapped event content:" . PHP_EOL;
    echo $event->getContent() . PHP_EOL . PHP_EOL;

    // Demonstrate decryption by recipients
    $wrappedContent = json_decode($event->getContent(), true);

    // Bob decrypts the message
    echo "Bob's decryption:" . PHP_EOL;
    echo "-----------------" . PHP_EOL;
    $bobConversationKey = Nip44::getConversationKey($bobPrivKey, $alicePubKey);
    foreach ($wrappedContent['seals'] as $seal) {
        if ($seal['pubkey'] === $bobPubKey) {
            // Decrypt the message key using Bob's conversation key
            $decryptedKey = Nip44::decrypt($seal['seal'], $bobConversationKey);
            // Use the decrypted key to decrypt the actual message
            $bobMessage = Nip44::decrypt($wrappedContent['content'], hex2bin($decryptedKey));
            echo "Decrypted by Bob: $bobMessage" . PHP_EOL;
            break;
        }
    }

    // Charlie decrypts the message
    echo PHP_EOL . "Charlie's decryption:" . PHP_EOL;
    echo "-------------------" . PHP_EOL;
    $charlieConversationKey = Nip44::getConversationKey($charliePrivKey, $alicePubKey);
    foreach ($wrappedContent['seals'] as $seal) {
        if ($seal['pubkey'] === $charliePubKey) {
            // Decrypt the message key using Charlie's conversation key
            $decryptedKey = Nip44::decrypt($seal['seal'], $charlieConversationKey);
            // Use the decrypted key to decrypt the actual message
            $charlieMessage = Nip44::decrypt($wrappedContent['content'], hex2bin($decryptedKey));
            echo "Decrypted by Charlie: $charlieMessage" . PHP_EOL;
            break;
        }
    }

    // Demonstrate that Eve cannot decrypt the message
    echo PHP_EOL . "Eve's attempt:" . PHP_EOL;
    echo "-------------" . PHP_EOL;
    $evePrivKey = $keyGenerator->generatePrivateKey();
    $evePubKey = $keyGenerator->getPublicKey($evePrivKey);
    $eveNpub = $keyGenerator->convertPublicKeyToBech32($evePubKey);
    echo "Eve's public key (npub): $eveNpub" . PHP_EOL;

    try {
        $eveConversationKey = Nip44::getConversationKey($evePrivKey, $alicePubKey);
        foreach ($wrappedContent['seals'] as $seal) {
            if ($seal['pubkey'] === $evePubKey) {
                $decryptedKey = Nip44::decrypt($seal['seal'], $eveConversationKey);
                $eveMessage = Nip44::decrypt($wrappedContent['content'], hex2bin($decryptedKey));
                echo "Eve somehow decrypted: $eveMessage" . PHP_EOL;
                break;
            }
        }
    } catch (Exception $e) {
        echo "Eve failed to decrypt (as expected): No seal found for Eve's key" . PHP_EOL;
    }
} catch (Exception $e) {
    echo "Exception error: " . $e->getMessage() . PHP_EOL;
}
