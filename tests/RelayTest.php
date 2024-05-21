<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use nostrverse\nostr\Event\Event;
use nostrverse\nostr\Key\Key;
use nostrverse\nostr\Relay\Relay;
use nostrverse\nostr\Relay\CommandResult;
use nostrverse\nostr\Sign\Sign;

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
            ->willReturn(new CommandResult(['OK', $note->getId(), true, '']));

        $response = $relay->send();
        $this->assertTrue(
            $response->isSuccess(),
        );
    }
}
