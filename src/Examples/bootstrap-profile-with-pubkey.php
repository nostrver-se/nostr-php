<?php

declare(strict_types=1);

require __DIR__ . '/../../vendor/autoload.php';

use swentel\nostr\Event\Event;
use swentel\nostr\Event\List\RelayListMetadata;
use swentel\nostr\Event\Profile\Profile;
use swentel\nostr\Filter\Filter;
use swentel\nostr\Message\RequestMessage;
use swentel\nostr\Relay\Relay;
use swentel\nostr\Relay\RelaySet;
use swentel\nostr\RelayResponse\RelayResponseEvent;
use swentel\nostr\Request\Request;
use swentel\nostr\Subscription\Subscription;

/**
 * As there is no single point of truth when it comes to the Nostr network (there are many),
 * it's a challenge where to begin for collecting data.
 * There are hundreds of relay which you could connect to, but many of them are serving for a specific need.
 * In this example we provide an example how to fetch metadata of a given pubkey into a Profile object.
 * Much metadata can be found in so-called lists and sets which are stored as events on relays.
 * See NIP-51 for an overview with these different types of lists: https://github.com/nostr-protocol/nips/blob/master/51.md
 */
try {
    // A known pubkey (sebastix)
    $pubkey = '06639a386c9c1014217622ccbcf40908c4f1a0c33e23f8d6d68f4abf655f8f71';
    /**
     * Within this class, there are some predefined relays which indexes profile related data as much as possible from the network.
     * These relays are considered as good relays to bootstrap and collect data.
     */
    $relayListMetadata = new RelayListMetadata($pubkey);
    // This is the list of relays where the given pubkey reads from.
    $readRelays = $relayListMetadata->getReadRelays();
    // This is the list of relays where the given pubkey writes (publishes) too.
    $writeRelays = $relayListMetadata->getWriteRelays();
    /**
     * Within the profile class, it will try to fetch as much us possible know metadata for this pubkey.
     * Now we know to which relays the pubkey is writing to, we (assume to) know from which relay we can read the profile data from.
     */
    $profile = new Profile();
    $profile->fetch($pubkey, $writeRelays[1] ?? null);
    print_r($profile) . PHP_EOL;

    // Get follow list (kind 3)
    $subscription = new Subscription();
    $filters = [];
    $filter = new Filter();
    $filter->setKinds([3]);
    $filter->setAuthors([$pubkey]);
    $filter->setLimit(1);
    $filters = [$filter];
    $relaySet = new RelaySet();
    foreach ($writeRelays as $relay_url) {
        $relay = new Relay($relay_url);
        $relaySet->addRelay($relay);
    }
    $requestMessage = new RequestMessage($subscription->getId(), $filters);
    $request = new Request($relaySet, $requestMessage);
    $response = $request->send();
    $following_lists = [];
    foreach ($response as $relayUrl => $relayResponses) {
        print 'Received ' . count($response[$relayUrl]) . ' message(s) received from relay ' . $relayUrl . PHP_EOL;
        foreach ($relayResponses as $relayResponse) {
            if ($relayResponse instanceof RelayResponseEvent && isset($relayResponse->event)) {
                $following_list_event = new Event();
                $following_list_event->populate($relayResponse->event);
                if (!isset($following_lists[$relayUrl][$following_list_event->getId()])) {
                    $following_lists[$relayUrl][$following_list_event->getId()] = $following_list_event;
                    $pTags = $following_list_event->getTag('p');
                    print 'Found following list with ' . count($pTags) . ' pubkeys on relay ' . $relayUrl . PHP_EOL;
                }
            }
        }
    }
    // We could now fetch the profile data for each of these pubkey and build a rich lists profiles followed by $pubkey.
    //print_r($following_lists);

    // TODO Get mute list (kind 10000)

    // TODO Get pinned notes (kind 10001)

    /*
     * TODO add other lists and sets too described on https://github.com/nostr-protocol/nips/blob/master/51.md
     */

} catch (\Exception $e) {
    print 'Exception error: ' . $e->getMessage() . PHP_EOL;
}
