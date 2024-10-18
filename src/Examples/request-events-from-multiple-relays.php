<?php

declare(strict_types=1);

use swentel\nostr\Event\Event;
use swentel\nostr\Filter\Filter;
use swentel\nostr\Message\RequestMessage;
use swentel\nostr\Relay\Relay;
use swentel\nostr\Relay\RelaySet;
use swentel\nostr\Request\Request;
use swentel\nostr\Subscription\Subscription;

require __DIR__ . '/../../vendor/autoload.php';

try {
    $subscription = new Subscription();
    $subscriptionId = $subscription->setId();

    $filter1 = new Filter();
    $filter1->setAuthors(
        [
            'npub1qe3e5wrvnsgpggtkytxteaqfprz0rgxr8c3l34kk3a9t7e2l3acslezefe', // Just Sebastix his npub.
        ],
    );
    $filter1->setKinds([1]);
    $filter1->setLimit(100);
    $filters = [$filter1];
    $requestMessage = new RequestMessage($subscriptionId, $filters);
    $relays = [
        new Relay('wss://nostr.sebastix.dev'),
        new Relay('wss://relay.damus.io'),
        new Relay('wss://welcome.nostr.wine'),
        new Relay('wss://nos.lol'),
        new Relay('wss://relay.nostr.band'),
        new Relay('wss://sebastix.social/relay'),
        new Relay('wss://nostr.wine'),
        new Relay('wss://pyramid.fiatjaf.com'),
    ];
    $relaySet = new RelaySet();
    $relaySet->setRelays($relays);
    $request = new Request($relaySet, $requestMessage);
    $response = $request->send();

    foreach ($response as $relayUrl => $relayResponses) {
        print 'Received ' . count($response[$relayUrl]) . ' message(s) found from relay ' . $relayUrl . PHP_EOL;
        foreach ($relayResponses as $message) {
            print $message->event->content . PHP_EOL;
        }
    }
} catch (Exception $e) {
    echo $e->getMessage();
}
