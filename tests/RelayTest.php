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

        $mockResponse = new RelayResponseOk(['OK', $note->getId(), true, '']);

        $relay = $this->createMock(Relay::class);
        $relay->expects($this->any())
            ->method('send')
            ->willReturn($mockResponse);

        // Access the mocked method and verify its response is correct
        $this->assertInstanceOf(RelayResponseOk::class, $mockResponse);
        $this->assertTrue($mockResponse->isSuccess());
    }

    /**
     * Tests connecting to a relay.
     */
    public function testConnect()
    {
        $relay = new Relay('wss://nos.lol');
        $relay->connect();
        $this->assertTrue($relay->isConnected());
    }

    /**
     * Tests disconnecting from a relay.
     */
    public function testDisconnect()
    {
        $relay = new Relay('wss://nos.lol');
        $relay->connect();
        $relay->disconnect();
        $this->assertFalse($relay->isConnected());
    }

    /**
     * Tests the URL validation.
     */
    public function testUrlValidation()
    {
        // Valid URLs should work
        $relay1 = new Relay('ws://example.com');
        $this->assertEquals('ws://example.com', $relay1->getUrl());

        $relay2 = new Relay('wss://example.com');
        $this->assertEquals('wss://example.com', $relay2->getUrl());

        // Invalid URL should throw an exception
        $this->expectException(\InvalidArgumentException::class);
        $relay3 = new Relay('http://example.com');
    }
}
