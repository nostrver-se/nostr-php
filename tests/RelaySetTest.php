<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use swentel\nostr\Event\Event;
use swentel\nostr\Key\Key;
use swentel\nostr\Relay\Relay;
use swentel\nostr\Relay\RelaySet;
use swentel\nostr\Sign\Sign;

class RelaySetTest extends TestCase
{
    private $actualRelayUrls = ['wss://nos.lol', 'wss://relay.primal.net'];

    /**
     * Tests sending a note to a set of relays.
     */
    public function testSendNoteToRelaySet()
    {
        $keys = new Key();
        $private_key = $keys->generatePrivateKey();

        $note = new Event();
        $note->setContent('Hello world');

        $signer = new Sign();
        $signer->signEvent($note, $private_key);

        $relay1 = new Relay('wss://example1.com');
        $relay2 = new Relay('wss://example2.com');
        $relay3 = new Relay('wss://example3.com');
        $this->expectException(\InvalidArgumentException::class);
        $relay4 = new Relay('http://not-wss.com');

        $relaySet = $this->createMock(RelaySet::class);
        $relaySet->method('getRelays')->willReturn([$relay1, $relay2, $relay3]);
        $relaySet->method('send')->willReturn([
            'wss://example1.com' => ['status' => 'success'],
            'wss://example2.com' => ['status' => 'success'],
            'wss://example3.com' => ['status' => 'success'],
        ]);

        $response = $relaySet->send();
        $this->assertIsArray($response);
    }

    /**
     * Tests removing a relay from a relay set.
     */
    public function testRemoveRelay()
    {
        $relay1 = new Relay('wss://example1.com');
        $relay2 = new Relay('wss://example2.com');
        $relay3 = new Relay('wss://example3.com');

        $relaySet = new RelaySet();
        $relaySet->setRelays([$relay1, $relay2, $relay3]);

        $this->assertCount(3, $relaySet->getRelays());

        $relaySet->removeRelay($relay2);

        $this->assertCount(2, $relaySet->getRelays());
        $this->assertContains($relay1, $relaySet->getRelays());
        $this->assertContains($relay3, $relaySet->getRelays());
        $this->assertNotContains($relay2, $relaySet->getRelays());
    }

    /**
     * Tests connecting to relays in a relay set.
     */
    public function testConnect()
    {
        $relaySet = new RelaySet();

        foreach ($this->actualRelayUrls as $url) {
            $relay = new Relay($url);
            $relaySet->addRelay($relay);
        }

        $this->assertFalse($relaySet->isConnected());

        $result = $relaySet->connect();

        $this->assertTrue($result);
        $this->assertTrue($relaySet->isConnected());
    }

    /**
     * Tests disconnecting from relays in a relay set.
     */
    public function testDisconnect()
    {
        $relaySet = new RelaySet();

        foreach ($this->actualRelayUrls as $url) {
            $relay = new Relay($url);
            $relaySet->addRelay($relay);
        }

        // First connect to the relays
        $relaySet->connect();
        $this->assertTrue($relaySet->isConnected());

        // Then disconnect
        $result = $relaySet->disconnect();

        $this->assertTrue($result);
        $this->assertFalse($relaySet->isConnected());
    }
}
