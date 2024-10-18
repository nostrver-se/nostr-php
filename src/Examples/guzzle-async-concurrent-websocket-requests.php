<?php

declare(strict_types=1);

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Handler\StreamHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Pool;
use GuzzleHttp\Psr7\Response;
use swentel\nostr\Filter\Filter;
use swentel\nostr\Message\RequestMessage;
use swentel\nostr\Relay\Relay;
use swentel\nostr\Relay\RelaySet;
use swentel\nostr\RelayResponse\RelayResponse;
use swentel\nostr\Subscription\Subscription;
use Valtzu\WebSocketMiddleware\WebSocketMiddleware;

require __DIR__ . '/../../vendor/autoload.php';

try {
    // The websocket middleware for the Guzzle client.
    $handlerStack = new HandlerStack(new StreamHandler());
    $handlerStack->push(new WebSocketMiddleware());
    // Guzzle client.
    $guzzle = new Client(['handler' => $handlerStack]);
    // Array with relays to transmit a message to.
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
    // Let's create a RelaySet.
    $relaySet = new RelaySet();
    $relaySet->setRelays($relays);
    //
    /** @var Closure $relaySetAsyncRequests */
    $relaySetAsyncRequests = function (RelaySet $relaySet) use ($guzzle) {
        $relays = $relaySet->getRelays();
        $countOfRelaysInRelaySet = count($relays);
        for ($i = 0; $i < $countOfRelaysInRelaySet; $i++) {
            /** @var Relay $relay */
            $relay = $relays[$i];
            $uri = $relay->getUrl();
            yield function () use ($guzzle, $uri) {
                return $guzzle->getAsync($uri);
            };
        }
    };
    //
    /** @var GuzzleHttp\Pool $pool */
    $pool = new Pool($guzzle, $relaySetAsyncRequests($relaySet), [
        // Maximum number of requests to send concurrently.
        'concurrency' => 20,
        // Array of request options to apply to each request to the client ($guzzle).
        // See https://docs.guzzlephp.org/en/stable/request-options.html.
        'options' => [],
        'fulfilled' => function (Response $response, $index) {
            // this is delivered each successful response
            /** @var \Valtzu\WebSocketMiddleware\WebSocketStream $ws */
            $ws = $response->getBody();
            $uri = $ws->getMetadata('uri');
            if ($response->getStatusCode() !== 101) {
                print $uri . ': ' . $response->getReasonPhrase() . PHP_EOL;
            } else {
                // Handle our websocket request here.
                $reqMsg = populateRequestMessage();
                $payload = $reqMsg->generate();
                print 'Writing to ' . $uri . ':' . PHP_EOL;
                print $payload . PHP_EOL;
                $ws->write($payload);
                do {
                    $ws_content = $ws->read();
                    if ($ws_content !== '') {
                        print 'Response from: ' . $uri . PHP_EOL;
                        print $ws_content . PHP_EOL;
                        print '-----------------------------------------------' . PHP_EOL;
                    }
                } while ($ws_content === '');
            }
        },
        'rejected' => function (RequestException $reason, $index) {
            // this is delivered each failed request
            print 'Request failed: ' . $reason->getMessage() . PHP_EOL;
        },
    ]);
    // Initiate the transfers and create a promise
    /** @var GuzzleHttp\Promise\Promise $promise */
    $promise = $pool->promise();
    // Force the pool of requests to complete.
    $promise->wait();
    if ($promise->getState() === 'fulfilled') {
        // We're done.
    } else {
        // get reason
        throw new RuntimeException(sprintf('Promise is not fulfilled, but got the state: %s', $promise->getState()));
    }
} catch (Exception $e) {
    print $e->getMessage() . PHP_EOL;
}

/**
 * With this request message we're fetching all kind 1 and 30023 event written by one given npub (the author).
 * A maximum of 250 of events will be return from each relay.
 * Feel free to adjust the filters depending on your needs.
 *
 * @return RequestMessage
 */
function populateRequestMessage(): RequestMessage
{
    $subscription = new Subscription();
    $subscriptionId = $subscription->setId();
    $filter1 = new Filter();
    $filter1->setAuthors(
        [
            'npub1qe3e5wrvnsgpggtkytxteaqfprz0rgxr8c3l34kk3a9t7e2l3acslezefe',
        ],
    );
    $filter1->setKinds([1, 30023]);
    $filter1->setLimit(250);
    $filters = [$filter1];
    return new RequestMessage($subscriptionId, $filters);
}
