<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use swentel\nostr\Event\Event;
use swentel\nostr\Key\Key;
use swentel\nostr\Relay\Relay;
use swentel\nostr\Relay\RelaySet;
use swentel\nostr\RelayResponse\RelayResponse;
use swentel\nostr\Sign\Sign;

class RelaySetTest extends TestCase
{
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

        $relaySet = $this->createMock(RelaySet::class);
        $relaySet->setRelays([$relay1, $relay2, $relay3]);
        $relaySet->expects($this->once())
            ->method('send')
            ->willReturn([
                $relaySet->getRelays(),
            ]);

        $response = $relaySet->send();
        $this->assertIsArray($response);
    }
}
