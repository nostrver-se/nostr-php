<?php

declare(strict_types=1);

use swentel\nostr\Filter\Filter;
use swentel\nostr\Message\RequestMessage;
use swentel\nostr\Relay\Relay;
use swentel\nostr\Request\Request;
use swentel\nostr\Subscription\Subscription;

require __DIR__ . '/../../vendor/autoload.php';

try {
    $subscription = new Subscription();
    $subscriptionId = $subscription->setId();

    $filter1 = new Filter();
    $filter1->setKinds([1]);
    $filter1->setLimit(25);
    $filters = [$filter1];
    $requestMessage = new RequestMessage($subscriptionId, $filters);
    $relay = new Relay('wss://relay.nostr.band');
    $request = new Request($relay, $requestMessage);
    $response = $request->send();

    /**
     * @var string $relayUrl
     *   The relay URL.
     * @var object $relayResponses
     *   RelayResponses which will contain the messages returned by the relay.
     *   Each message will also contain the event.
     */
    foreach ($response as $relayUrl => $relayResponses) {
        print 'Received ' . count($response[$relayUrl]) . ' message(s) found from relay ' . $relayUrl . PHP_EOL;
        /** @var \swentel\nostr\RelayResponse\RelayResponseEvent $message */
        foreach ($relayResponses as $message) {
            print $message->event->content . PHP_EOL;
        }
    }
} catch (Exception $e) {
    print 'Exception error: ' . $e->getMessage() . '\n';
}
