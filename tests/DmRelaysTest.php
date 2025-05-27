<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use swentel\nostr\Event\List\DmRelaysList;
use swentel\nostr\Key\Key;

/**
 * Test class for fetching DM relay list functionality.
 */
class DmRelaysTest extends TestCase
{
    public function testGetDmRelaysList(): void
    {
        $DmRelaysListMock = $this->createMock(DmRelaysList::class);
        $key = new Key();
        $privKey = $key->generatePrivateKey();
        $pubKey = $key->getPublicKey($privKey);
        $DmRelaysListMock->method('getRelays')
            ->with($pubKey, 'wss://example.com')
            ->willReturn(['wss://relay1.com', 'wss://relay2.com', 'wss://relay3.com']);

        $this->assertIsArray($DmRelaysListMock->getTag('relay'));
        $result = $DmRelaysListMock->getRelays($pubKey, 'wss://example.com');
        $this->assertIsArray($result);
        $this->assertNotEmpty($result);
    }

    public function testGetEmptyDmRelaysList(): void
    {
        $DmRelaysListMock = $this->createMock(DmRelaysList::class);
        $key = new Key();
        $privKey = $key->generatePrivateKey();
        $pubKey = $key->getPublicKey($privKey);
        $DmRelaysListMock->method('getRelays')
            ->with($pubKey)
            ->willReturnMap([
                [$pubKey, []],
            ]);
        $result = $DmRelaysListMock->getRelays($pubKey);
        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    public function testValidKind(): void
    {
        $DmRelaysListClass = new DmRelaysList();
        $this->assertIsInt($DmRelaysListClass->getKind());
        $this->assertEquals(10050, $DmRelaysListClass->getKind());

        $DmRelaysListMock = $this->createMock(DmRelaysList::class);
        $DmRelaysListMock->method('getKind')
            ->willReturn(10050);

        $kind = $DmRelaysListMock->getKind();
        $this->assertIsInt($kind);
        $this->assertEquals(10050, $kind);
    }
}
