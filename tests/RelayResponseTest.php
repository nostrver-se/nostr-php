<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use swentel\nostr\Filter\Filter;
use swentel\nostr\Message\RequestMessage;
use swentel\nostr\Relay\Relay;
use swentel\nostr\RelayResponse\RelayResponseAuth;
use swentel\nostr\RelayResponse\RelayResponseClosed;
use swentel\nostr\RelayResponse\RelayResponseOk;
use swentel\nostr\Request\Request;
use swentel\nostr\Subscription\Subscription;

class RelayResponseTest extends TestCase
{
    public function testSendRequestToRelayAndResultAuth()
    {
        $relayUrl = 'wss://jingle.nostrver.se';

        $relay = new Relay($relayUrl);

        $subscription = new Subscription();
        $subscriptionId = $subscription->setId();

        $filter = new Filter();
        $filter->setKinds([1]);
        $filter->setLimit(1);

        $filters = [$filter];

        $requestMessage = new RequestMessage($subscriptionId, $filters);
        $request = new Request($relay, $requestMessage);

        $result = $request->send();

        $this->assertInstanceOf(RelayResponseAuth::class, $result[$relayUrl][0]);
        $this->assertInstanceOf(RelayResponseClosed::class, $result[$relayUrl][1]);
        $this->assertInstanceOf(RelayResponseOk::class, $result[$relayUrl][2]);
    }
}
