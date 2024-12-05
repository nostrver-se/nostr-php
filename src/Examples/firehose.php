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

    $filter1 = new Filter();
    $filter1->setKinds([1]);
    $filter1->setLimit(1);
    $filters = [$filter1];
    $requestMessage = new RequestMessage($subscription->getId(), $filters);
    $relay = new Relay('wss://relay.nostr.band');
    $request = new Request($relay, $requestMessage);
    $request->setFireHose(true);
    $response = $request->send();

    // TODO implement the websocket client here

    /**
     * @var string $relayUrl
     *   The relay URL.
     * @var object $relayResponse
     *   RelayResponse which will contain the messages returned by the relay.
     *   Each message will also contain the event.
     */
    foreach ($response as $relayUrl => $relayResponses) {
        print 'Received ' . count($response[$relayUrl]) . ' message(s) received from relay ' . $relayUrl . PHP_EOL;
        /** @var \swentel\nostr\RelayResponse\RelayResponseEvent $message */
        foreach ($relayResponses as $relayResponse) {
            if (isset($relayResponse->event->content)) {
                print $relayResponse->event->content . PHP_EOL;
            }
        }
    }
} catch (Exception $e) {
    print 'Exception error: ' . $e->getMessage() . PHP_EOL;
}
