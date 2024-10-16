<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use swentel\nostr\Relay\Relay;
use swentel\nostr\RelayResponse\RelayResponseEvent;
use swentel\nostr\Subscription\Subscription;
use swentel\nostr\Filter\Filter;
use swentel\nostr\Message\RequestMessage;
use swentel\nostr\Request\Request;

class RequestTest extends TestCase
{
    /**
     * Tests sending a request to a relay.
     */
    public function testSendRequestToRelay()
    {
        $relayUrl = 'wss://relay.damus.io';

        $relay = new Relay($relayUrl);

        // Test retrieving events from relay
        $subscription = new Subscription();
        $subscriptionId = $subscription->setId();

        $filter = new Filter();
        $filter->setKinds([1]);
        $filter->setLimit(3);

        $filters = [$filter];

        $requestMessage = new RequestMessage($subscriptionId, $filters);
        $request = new Request($relay, $requestMessage);

        $result = $request->send();

        $this->assertInstanceOf(RelayResponseEvent::class, $result[$relayUrl][0]);
        $this->assertNotEmpty($result, 'Request send result should not be empty');
    }
}
