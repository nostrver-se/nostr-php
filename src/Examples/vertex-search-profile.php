<?php

declare(strict_types=1);

use swentel\nostr\Event\Event;
use swentel\nostr\Filter\Filter;
use swentel\nostr\Key\Key;
use swentel\nostr\Message\EventMessage;
use swentel\nostr\Message\RequestMessage;
use swentel\nostr\Relay\Relay;
use swentel\nostr\RelayResponse\RelayResponseOk;
use swentel\nostr\Request\Request;
use swentel\nostr\Sign\Sign;

require __DIR__ . '/../../vendor/autoload.php';

/**
 * This snippet show how to integrate the
 * Search Profiles endpoint of Vertex.
 * Docs: https://vertexlab.io/docs/endpoints/search-profiles/
 */

try {
    $event = new Event();
    $event->setKind(5315);
    $search = 'jack';
    $sort = 'globalPagerank';
    $limit = "10";
    $event->setTags([
        ['param', 'search', $search],
        ['param', 'sort', $sort],
        ['param', 'limit', $limit],
    ]);
    /**
     * You should use a private key here with credits from Vertex.
     */
    $private_key = new Key();
    $private_key = $private_key->generatePrivateKey();
    $signer = new Sign();
    $signer->signEvent($event, $private_key);
    // Send search request event to relay wss://relay.vertexlab.io
    $relay = new Relay('wss://relay.vertexlab.io');
    $eventMessage = new EventMessage($event);
    $relay->setMessage($eventMessage);
    $request = new Request($relay, $eventMessage);
    $response = $request->send();
    foreach ($response as $relayUrl => $relayResponses) {
        foreach ($relayResponses as $relayResponse) {
            if ($relayResponse->isSuccess && $relayResponse instanceof RelayResponseOk) {
                $event_id = $relayResponse->eventId;
                echo $event_id . PHP_EOL;
                $relay = new Relay($relayUrl);
                $filter = new Filter();
                $filter->setKinds([6315, 7000]);
                $filter->setTags([
                    '#e' => [$event_id],
                ]);
                $filters = [$filter];
                $requestMessage = new RequestMessage($event_id, $filters);
                $request = new Request($relay, $requestMessage);
                $responses = $request->send();
                if (empty($responses)) {
                    throw new \RuntimeException(sprintf('Response from relay %s is empty', $relay->getUrl()));
                }
                print_r($responses) . PHP_EOL;
            }
        }
    }
} catch (Exception $e) {
    print 'Exception error: ' . $e->getMessage() . PHP_EOL;
}
