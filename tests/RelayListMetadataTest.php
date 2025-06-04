<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use swentel\nostr\Event\List\RelayListMetadata;
use swentel\nostr\Relay\Relay;
use swentel\nostr\RelayResponse\RelayResponseEvent;
use swentel\nostr\Request\Request;

class RelayListMetadataTest extends TestCase
{
    private const TEST_PUBKEY = '884704bd421721e292edbec8466287dd3a3c834c2ed269822b95a85e4f8a0c47';
    private const TEST_RELAY_URL = 'wss://purplepag.es';

    /**
     * Test that the kind number cannot be changed from 10002.
     */
    public function testKindNumberIsFixed(): void
    {
        $relayList = new RelayListMetadata(self::TEST_PUBKEY);
        $this->assertEquals(10002, $relayList->getKind());
    }

    /**
     * Test fetching relay list with empty response.
     */
    public function testEmptyRelayList(): void
    {
        $mockRelay = $this->createMock(Relay::class);
        $mockRequest = $this->createMock(Request::class);
        $mockRequest->method('send')->willReturn([]);

        $relayList = new RelayListMetadata(self::TEST_PUBKEY);
        $this->assertIsArray($relayList->getRelays());
        $this->assertEmpty($relayList->getRelays());
    }

    /**
     * Test getting write relays.
     */
    public function testGetWriteRelays(): void
    {
        $relayList = $this->createRelayListWithMockData([
            ['r', 'wss://relay1.com', 'write'],
            ['r', 'wss://relay2.com', 'read'],
            ['r', 'wss://relay3.com'], // Both read and write
            ['r', 'wss://relay4.com', 'write'],
        ]);

        $writeRelays = $relayList->getWriteRelays();

        $this->assertContains('wss://relay1.com', $writeRelays);
        $this->assertNotContains('wss://relay2.com', $writeRelays);
        $this->assertContains('wss://relay3.com', $writeRelays);
        $this->assertContains('wss://relay4.com', $writeRelays);
        $this->assertNotContains('http://invalid.com', $writeRelays);
    }

    /**
     * Test getting read relays.
     */
    public function testGetReadRelays(): void
    {
        $relayList = $this->createRelayListWithMockData([
            ['r', 'wss://relay1.com', 'read'],
            ['r', 'wss://relay2.com', 'write'],
            ['r', 'wss://relay3.com'], // Both read and write
            ['r', 'wss://relay4.com', 'read'],
        ]);

        $readRelays = $relayList->getReadRelays();

        $this->assertContains('wss://relay1.com', $readRelays);
        $this->assertNotContains('wss://relay2.com', $readRelays);
        $this->assertContains('wss://relay3.com', $readRelays);
        $this->assertContains('wss://relay4.com', $readRelays);
        $this->assertNotContains('http://invalid.com', $readRelays);
    }

    /**
     * Test that fallback relays are queried when primary relay returns no results.
     */
    public function testFallbackRelayQuerying(): void
    {
        $relayList = $this->createRelayListWithMockData(
            [['r', 'wss://fallback-relay.com', 'write']],
            true,
        );

        $writeRelays = $relayList->getWriteRelays();
        $this->assertContains('wss://fallback-relay.com', $writeRelays);
    }

    /**
     * Test handling of malformed relay URLs.
     */
    public function testMalformedRelayUrls(): void
    {
        $relayList = $this->createRelayListWithMockData([
            ['r', 'not-a-url', 'write'],
            ['r', 'http://not-secure.com', 'write'],
            ['r', 'wss://valid.com', 'write'],
        ]);

        $this->expectException(\RuntimeException::class);
        $relayList->getWriteRelays();

    }

    /**
     * Creates a RelayListMetadata instance with mock data.
     */
    private function createRelayListWithMockData(array $tags, bool $useFallback = false): RelayListMetadata
    {
        // Create a mock event response
        $mockEvent = new \stdClass();
        $mockEvent->tags = $tags;

        // Create a mock relay response
        $mockRelayResponse = $this->createMock(RelayResponseEvent::class);
        $mockRelayResponse->event = $mockEvent;

        // Create reflection class to modify private properties
        $relayList = new RelayListMetadata(self::TEST_PUBKEY);
        $reflection = new \ReflectionClass($relayList);

        $relaysProperty = $reflection->getProperty('relays');
        $relaysProperty->setAccessible(true);
        $relaysProperty->setValue($relayList, $tags);

        return $relayList;
    }

    /**
     * Test that getKnownRelays returns expected relays.
     */
    public function testGetKnownRelays(): void
    {
        $relayList = new RelayListMetadata(self::TEST_PUBKEY);
        $reflection = new \ReflectionClass($relayList);

        $method = $reflection->getMethod('getKnownRelays');
        $method->setAccessible(true);

        $knownRelays = $method->invoke($relayList);

        $this->assertIsArray($knownRelays);
        $this->assertNotEmpty($knownRelays);
        foreach ($knownRelays as $relay) {
            $this->assertStringStartsWith('wss://', $relay);
        }
    }

    /**
     * Test that empty relays throw exception for all getter methods.
     */
    public function testEmptyRelaysThrowExceptions(): void
    {
        $relayList = new RelayListMetadata(self::TEST_PUBKEY);
        $reflection = new \ReflectionClass($relayList);

        $relaysProperty = $reflection->getProperty('relays');
        $relaysProperty->setAccessible(true);
        $relaysProperty->setValue($relayList, []);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('The relays property is empty of swentel\nostr\Event\List\RelayListMetadata');

        $relayList->getRelays();
        $relayList->getReadRelays();
        $relayList->getWriteRelays();
    }
}
