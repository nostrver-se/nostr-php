<?php

declare(strict_types=1);

require __DIR__ . '/../../vendor/autoload.php';

use swentel\nostr\Event\List\DmRelaysList;

try {
    // Request list with DM relays for a given pubkey.
    $dmRelaysList = new DmRelaysList();
    $pubkey = '06639a386c9c1014217622ccbcf40908c4f1a0c33e23f8d6d68f4abf655f8f71';
    $dmRelays = $dmRelaysList->getRelays($pubkey);
    print_r($dmRelays) . PHP_EOL;
    // @todo: Publish profile (kind 10050) event

} catch (\Exception $e) {
    print 'Exception error: ' . $e->getMessage() . PHP_EOL;
}
