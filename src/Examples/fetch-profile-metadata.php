<?php

declare(strict_types=1);

require __DIR__ . '/../../vendor/autoload.php';

use swentel\nostr\Event\Profile\Profile;

try {
    // Request profile (kind 0) event
    $profile = new Profile();
    $pubkey = '06639a386c9c1014217622ccbcf40908c4f1a0c33e23f8d6d68f4abf655f8f71';
    $profile->fetch($pubkey);
    print_r($profile) . PHP_EOL;
    // @todo: Publish profile (kind 0) event

} catch (\Exception $e) {
    print 'Exception error: ' . $e->getMessage() . PHP_EOL;
}
