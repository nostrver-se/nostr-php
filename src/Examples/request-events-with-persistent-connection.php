<?php

declare(strict_types=1);

use swentel\nostr\Filter\Filter;
use swentel\nostr\Message\RequestMessage;
use swentel\nostr\Relay\Relay;
use swentel\nostr\Request\PersistentConnection;
use swentel\nostr\Subscription\Subscription;

require __DIR__ . '/../../vendor/autoload.php';

try {
    $subscription = new Subscription();

    $filter1 = new Filter();
    $filter1->setKinds([1]);
    $filter1->setLimit(25);
    $filters = [$filter1];
    $ReqMessage = new RequestMessage($subscription->getId(), $filters);
    $relay = new Relay('wss://relay.nostr.band');
    $connection = new PersistentConnection($relay, $ReqMessage);
    $response = $connection->transmit();

} catch (Exception $e) {
    print 'Exception error: ' . $e->getMessage() . PHP_EOL;
}
