<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use swentel\nostr\Event\Event;
use swentel\nostr\Key\Key;
use swentel\nostr\Relay\Relay;
use swentel\nostr\RelayResponse\RelayResponseOk;
use swentel\nostr\Sign\Sign;

class RelayTest extends TestCase
{
    /**
     * Tests sending a note to a relay.
     */
    public function testSendNoteToRelay()
    {
        $keys = new Key();
        $private_key = $keys->generatePrivateKey();

        $note = new Event();
        $note->setContent('Hello world');

        $signer = new Sign();
        $signer->signEvent($note, $private_key);

        $relay = $this->createMock(Relay::class);
        $relay->expects($this->once())
            ->method('send')
            ->willReturn(new RelayResponseOk(['OK', $note->getId(), true, '']));

        $response = $relay->send();
        $this->assertTrue(
            $response->isSuccess(),
        );
    }
}
