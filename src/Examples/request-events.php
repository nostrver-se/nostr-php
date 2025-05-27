<?php

declare(strict_types=1);

use swentel\nostr\Event\Event;
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
    $filter1->setLimit(25);
    $filters = [$filter1];
    $requestMessage = new RequestMessage($subscription->getId(), $filters);
    $relay = new Relay('wss://relay.nostr.band');
    $request = new Request($relay, $requestMessage);
    $response = $request->send();

    /**
     * @var string $relayUrl
     *   The relay URL.
     * @var object $relayResponse
     *   RelayResponse which will contain the messages returned by the relay.
     *   Each message will also contain the event.
     */
    if (empty($response)) {
        throw new \RuntimeException(sprintf('Response from relay %s is empty', $relay->getUrl()));
    }
    // Array for the events we're fetching from the relay
    $events = [];
    foreach ($response as $relayUrl => $relayResponses) {
        print 'Received ' . count($response[$relayUrl]) . ' message(s) received from relay ' . $relayUrl . PHP_EOL;
        /** @var \swentel\nostr\RelayResponse\RelayResponseEvent $relayResponse */
        foreach ($relayResponses as $relayResponse) {
            if (isset($relayResponse->event->content)) {
                // Save event to array
                $events[] = $relayResponse->event;
                print $relayResponse->event->content . PHP_EOL;
            }
        }
    }
    if (empty($events)) {
        throw new \RuntimeException('Event array is empty');
    }
    foreach ($events as $index => $event) {
        // Populate PHP object with event data to a Nostr event object
        $e = new Event();
        $e->populate($event);
        // Replace objects with Nostr event object in $events array
        $events[$index] = $e;
    }
} catch (Exception $e) {
    print 'Exception error: ' . $e->getMessage() . PHP_EOL;
}
