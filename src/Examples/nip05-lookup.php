<?php

declare(strict_types=1);

/**
 * Example script demonstrating the NIP-05 lookup functionality.
 *
 * Usage: php nip05-lookup.php [identifier]
 * If no identifier is provided, it will use 'sebastian@sebastix.dev' as default.
 */

use swentel\nostr\Nip05\Nip05Helper;
use swentel\nostr\Nip19\Nip19Helper;

require_once __DIR__ . '/../../vendor/autoload.php';

try {
    // Get the identifier from command line arguments or use default
    $identifier = $argv[1] ?? 'sebastian@sebastix.dev';

    // Set up the necessary classes
    $nip05 = new Nip05Helper();
    $nip19 = new Nip19Helper();

    // Display lookup header
    print "Looking up NIP-05 identifier: $identifier" . PHP_EOL;
    print PHP_EOL;

    // Get the public key
    $pubkey = $nip05->getPublicKey($identifier);
    if (!$pubkey) {
        throw new \RuntimeException("Could not find public key for $identifier");
    }

    // Convert the public key to npub format
    $npub = $nip19->encodeNpub($pubkey);

    // Get the relays
    $relays = $nip05->getRelays($identifier);

    // Output the results
    print "Results for $identifier:" . PHP_EOL;
    print "--------------------------------------------------------------------------------" . PHP_EOL;
    print "Public key (hex): $pubkey" . PHP_EOL;
    print "Public key (npub): $npub" . PHP_EOL;
    print PHP_EOL . "Relays:" . PHP_EOL;

    if ($relays && count($relays) > 0) {
        foreach ($relays as $relay) {
            print "- $relay" . PHP_EOL;
        }
    } else {
        throw new \RuntimeException("No relays found for this identifier" . PHP_EOL);
    }
    print "--------------------------------------------------------------------------------" . PHP_EOL;
} catch (\Exception $e) {
    print 'Exception error: ' . $e->getMessage() . PHP_EOL;
}
