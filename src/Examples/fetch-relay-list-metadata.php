<?php

declare(strict_types=1);

require __DIR__ . '/../../vendor/autoload.php';

use swentel\nostr\Event\List\RelayListMetadata;

try {
    // Request relay list for a given pubkey where to read from and write to.
    $pubkey = '06639a386c9c1014217622ccbcf40908c4f1a0c33e23f8d6d68f4abf655f8f71';
    $relayListMetadata = new RelayListMetadata($pubkey);

    $relays = $relayListMetadata->getRelays();
    print_r($relays) . PHP_EOL;

    $writeRelays = $relayListMetadata->getWriteRelays();
    print_r($writeRelays) . PHP_EOL;

    $readRelays = $relayListMetadata->getReadRelays();
    print_r($readRelays) . PHP_EOL;

} catch (\Exception $e) {
    print 'Exception error: ' . $e->getMessage() . PHP_EOL;
}
