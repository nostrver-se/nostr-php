<?php

declare(strict_types=1);

use swentel\nostr\Filter\Filter;
use swentel\nostr\Message\RequestMessage;
use swentel\nostr\Relay\Relay;
use swentel\nostr\RelayResponse\RelayResponse;
use swentel\nostr\RelayResponse\RelayResponseEvent;
use swentel\nostr\Request\Request;
use swentel\nostr\Subscription\Subscription;

require __DIR__ . '/../../vendor/autoload.php';

try {
    $subscription = new Subscription();

    $filter1 = new Filter();
    $filter1->setKinds([1]);
    $filter1->setLimit(25);
    /**
     * Please have a look at this overview with tags you can use according to the Nostr NIPs:
     * https://nips.nostr.com/#standardized-tags
     */
    // Apply multiple tags to the filter.
    $filter1->setTags(
        [
            '#t' => ['PHP', 'Drupal'],
            '#p' => ['06639a386c9c1014217622ccbcf40908c4f1a0c33e23f8d6d68f4abf655f8f71'],
        ],
    );
    // Apply a single tag to the filter which in this case will be appended to the #t tag.
    $filter1->setTag('#t', ['Wordpress']);
    // Apply an e-tag.
    //$filter1->setLowercaseETags([
    //    '06639a386c9c1014217622ccbcf40908c4f1a0c33e23f8d6d68f4abf655f8f71'
    //]);
    // Apply a p-tag.
    //$filter1->setLowercasePTags([
    //    '06639a386c9c1014217622ccbcf40908c4f1a0c33e23f8d6d68f4abf655f8f71'
    //]);
    /**
     * If you're using multiple conditions in one filter, these conditions are interpreted as && (AND) conditions.
     * If you would like to use || (OR) conditions, you should use multiple filters.
     */
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
    foreach ($response as $relayUrl => $relayResponses) {
        print 'Received ' . count($response[$relayUrl]) . ' message(s) received from relay ' . $relayUrl . PHP_EOL;
        /** @var RelayResponse $relayResponse */
        foreach ($relayResponses as $relayResponse) {
            if ($relayResponse instanceof RelayResponseEvent) {
                if (isset($relayResponse->event->content)) {
                    print $relayResponse->event->content . PHP_EOL;
                }
            }
        }
    }
} catch (Exception $e) {
    print 'Exception error: ' . $e->getMessage() . PHP_EOL;
}
