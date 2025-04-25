<?php

declare(strict_types=1);

/**
 * Example script demonstrating the NIP-05 lookup functionality.
 *
 * Usage: php nip05-lookup.php [identifier]
 * If no identifier is provided, it will use 'sebastian@sebastix.dev' as default.
 */

use swentel\nostr\Nip05\Nip05;
use swentel\nostr\Nip19\Nip19Helper;

require_once __DIR__ . '/../../vendor/autoload.php';

// Get the identifier from command line arguments or use default
$identifier = $argv[1] ?? 'sebastian@sebastix.dev';

// Set up the necessary classes
$nip05 = new Nip05();
$nip19 = new Nip19Helper();

// Display lookup header
echo "Looking up NIP-05 identifier: $identifier\n\n";

// Get the public key
$pubkey = $nip05->getPublicKey($identifier);
if (!$pubkey) {
    echo "Could not find public key for $identifier\n";
    exit(1);
}

// Convert the public key to npub format
$npub = $nip19->encodeNpub($pubkey);

// Get the relays
$relays = $nip05->getRelays($identifier);

// Output the results
echo "Results for $identifier:\n";
echo "--------------------------------------------------------------------------------\n";
echo "Public key (hex): $pubkey\n";
echo "Public key (npub): $npub\n";
echo "\nRelays:\n";

if ($relays && count($relays) > 0) {
    foreach ($relays as $relay) {
        echo "- $relay\n";
    }
} else {
    echo "No relays found for this identifier\n";
}
echo "--------------------------------------------------------------------------------\n";
