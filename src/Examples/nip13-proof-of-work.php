<?php

declare(strict_types=1);

/**
 * Example script demonstrating the NIP-13 (Proof of Work) functionality.
 *
 * Usage: php nip13-proof-of-work.php .
 */

use swentel\nostr\Nip13\Nip13Helper;
use swentel\nostr\Event\Event;
use swentel\nostr\Key\Key;
use swentel\nostr\Sign\Sign;

require_once __DIR__ . '/../../vendor/autoload.php';

try {
    $targetDifficulty = 21;

    $event = new Event();
    $event->setKind(1);
    $event->setTags([]);
    $event->setContent('Hello, world!');
    $event->setCreatedAt(0);
    $event->setPublicKey('79c2cae114ea28a981e7559b4fe7854a473521a8d22a66bbab9fa248eb820ff6');

    $event = Nip13Helper::minePow($event, $targetDifficulty);

    $private_key = new Key();
    $private_key = $private_key->generatePrivateKey();
    $signer = new Sign();
    $signer->signEvent($event, $private_key);

    if (Nip13Helper::getPowDifficulty($event->getId()) >= $targetDifficulty) {
        print "Event mined successfully with difficulty $targetDifficulty " . $event->getId() . PHP_EOL;
    } else {
        print "Event mining failed. Difficulty: " . Nip13Helper::getPowDifficulty($event->getId()) . PHP_EOL;
    }
} catch (\Exception $e) {
    print 'Exception error: ' . $e->getMessage() . PHP_EOL;
}
