<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use swentel\nostr\Relay\Relay;
use swentel\nostr\Subscription\Subscription;
use swentel\nostr\Filter\Filter;
use swentel\nostr\Message\RequestMessage;
use swentel\nostr\Request\Request;
use WebSocket\Client;

class RequestTest extends TestCase
{
    /**
     * Tests sending a request to a relay.
     */
    public function testSendRequestToRelay()
    {
        $relayUrl = 'wss://relay.damus.io';

        $subscription = new Subscription();
        $subscriptionId = $subscription->setId();

        $filter = new Filter();
        $filter->setKinds([1]);
        $filter->setLimit(3);

        $filters = [$filter];

        $relay = new Relay($relayUrl);

        // Mocking the WebSocket\Client
        $mockClient = $this->getMockBuilder(Client::class)
            ->setConstructorArgs([$relay->getUrl()])
            ->getMock();

        $requestMessage = new RequestMessage($subscriptionId, $filters);
        $request = new Request($relay, $requestMessage, $mockClient);

        $result = $request->send();

        $this->assertNotEmpty($result, 'Request send result should not be empty');
    }
}
