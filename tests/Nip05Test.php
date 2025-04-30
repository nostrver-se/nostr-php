<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use swentel\nostr\Nip05\Nip05Helper;

/**
 * Test class for NIP-05 functionality.
 */
class Nip05Test extends TestCase
{
    /**
     * Tests the parseIdentifier method with valid identifiers.
     */
    public function testParseValidIdentifier(): void
    {
        $nip05 = $this->getMockBuilder(Nip05Helper::class)
            ->onlyMethods(['fetchJson'])
            ->getMock();

        // Use reflection to access the private method
        $reflection = new \ReflectionClass($nip05);
        $method = $reflection->getMethod('parseIdentifier');
        $method->setAccessible(true);

        // Test regular identifiers
        $result = $method->invoke($nip05, 'bob@example.com');
        $this->assertEquals(['name' => 'bob', 'domain' => 'example.com'], $result);

        // Test with underscore and dot
        $result = $method->invoke($nip05, 'bob_smith.test@example.com');
        $this->assertEquals(['name' => 'bob_smith.test', 'domain' => 'example.com'], $result);

        // Test with dash and underscore
        $result = $method->invoke($nip05, 'bob-smith_test@example.com');
        $this->assertEquals(['name' => 'bob-smith_test', 'domain' => 'example.com'], $result);

        // Test "_" local part for root display
        $result = $method->invoke($nip05, '_@example.com');
        $this->assertEquals(['name' => '_', 'domain' => 'example.com'], $result);
    }

    /**
     * Tests the parseIdentifier method with invalid identifiers.
     */
    public function testParseInvalidIdentifier(): void
    {
        $nip05 = $this->getMockBuilder(Nip05Helper::class)
            ->onlyMethods(['fetchJson'])
            ->getMock();

        // Use reflection to access the private method
        $reflection = new \ReflectionClass($nip05);
        $method = $reflection->getMethod('parseIdentifier');
        $method->setAccessible(true);

        // Test invalid identifiers
        $this->assertFalse($method->invoke($nip05, 'invalid'));
        $this->assertFalse($method->invoke($nip05, 'invalid@'));
        $this->assertFalse($method->invoke($nip05, '@example.com'));
        $this->assertFalse($method->invoke($nip05, 'bob@@example.com'));
        $this->assertFalse($method->invoke($nip05, 'bob@example@com'));
    }

    /**
     * Tests the verify method with a matching pubkey.
     */
    public function testVerifySuccess(): void
    {
        // Create a mock of the Nip05
        $nip05 = $this->createMock(Nip05Helper::class);

        // Setup the mock to return true for the verify method
        $nip05->method('verify')
            ->with('bob@example.com', 'b0635d6a9851d3aed0cd6c495b282167acf761729078d975fc341b22650b07b9')
            ->willReturn(true);

        $result = $nip05->verify('bob@example.com', 'b0635d6a9851d3aed0cd6c495b282167acf761729078d975fc341b22650b07b9');
        $this->assertTrue($result);
    }

    /**
     * Tests the verify method with a non-matching pubkey.
     */
    public function testVerifyFailure(): void
    {
        // Create a mock of the Nip05
        $nip05 = $this->createMock(Nip05Helper::class);

        // Setup the mock to return false for the verify method
        $nip05->method('verify')
            ->with('bob@example.com', 'different_pubkey')
            ->willReturn(false);

        $result = $nip05->verify('bob@example.com', 'different_pubkey');
        $this->assertFalse($result);
    }

    /**
     * Tests the getPublicKey method.
     */
    public function testGetPublicKey(): void
    {
        // Create a mock of the Nip05
        $nip05 = $this->createMock(Nip05Helper::class);

        // Setup the mock to return a pubkey for the getPublicKey method
        $nip05->method('getPublicKey')
            ->with('bob@example.com')
            ->willReturn('b0635d6a9851d3aed0cd6c495b282167acf761729078d975fc341b22650b07b9');

        $result = $nip05->getPublicKey('bob@example.com');
        $this->assertEquals('b0635d6a9851d3aed0cd6c495b282167acf761729078d975fc341b22650b07b9', $result);
    }

    /**
     * Tests the getPublicKey method with missing data.
     */
    public function testGetPublicKeyMissing(): void
    {
        // Create a mock of the Nip05
        $nip05 = $this->createMock(Nip05Helper::class);

        // Setup the mock to return null for the getPublicKey method
        $nip05->method('getPublicKey')
            ->with('bob@example.com')
            ->willReturn(null);

        $result = $nip05->getPublicKey('bob@example.com');
        $this->assertNull($result);
    }

    /**
     * Tests the getRelays method.
     */
    public function testGetRelays(): void
    {
        // Create a mock of the Nip05
        $nip05 = $this->createMock(Nip05Helper::class);

        $relays = ['wss://relay.example.com', 'wss://relay2.example.com'];

        // Setup the mock to return relays for the getRelays method
        $nip05->method('getRelays')
            ->with('bob@example.com', null)
            ->willReturn($relays);

        $result = $nip05->getRelays('bob@example.com');
        $this->assertEquals($relays, $result);
    }

    /**
     * Tests the getRelays method with a specified pubkey.
     */
    public function testGetRelaysWithSpecifiedPubkey(): void
    {
        // Create a mock of the Nip05
        $nip05 = $this->createMock(Nip05Helper::class);

        $pubkey = 'b0635d6a9851d3aed0cd6c495b282167acf761729078d975fc341b22650b07b9';
        $relays = ['wss://relay.example.com', 'wss://relay2.example.com'];

        // Setup the mock to return relays for the getRelays method with a specified pubkey
        $nip05->method('getRelays')
            ->with('bob@example.com', $pubkey)
            ->willReturn($relays);

        $result = $nip05->getRelays('bob@example.com', $pubkey);
        $this->assertEquals($relays, $result);
    }

    /**
     * Tests the getRelays method with missing relays.
     */
    public function testGetRelaysMissing(): void
    {
        // Create a mock of the Nip05
        $nip05 = $this->createMock(Nip05Helper::class);

        // Setup the mock to return null for the getRelays method
        $nip05->method('getRelays')
            ->with('bob@example.com', null)
            ->willReturn(null);

        $result = $nip05->getRelays('bob@example.com');
        $this->assertNull($result);
    }

    /**
     * Tests the formatForDisplay method.
     */
    public function testFormatForDisplay(): void
    {
        // Create a mock of the Nip05
        $nip05 = $this->createMock(Nip05Helper::class);

        // Setup the mock to return formatted identifiers
        $nip05->method('formatForDisplay')
            ->willReturnMap([
                ['bob@example.com', 'bob@example.com'],
                ['alice@nostr.com', 'alice@nostr.com'],
                ['_@example.com', 'example.com'],
                ['_@nostr.com', 'nostr.com'],
                ['invalid', 'invalid'],
                ['@example.com', '@example.com'],
            ]);

        // Test normal identifiers remain unchanged
        $this->assertEquals('bob@example.com', $nip05->formatForDisplay('bob@example.com'));
        $this->assertEquals('alice@nostr.com', $nip05->formatForDisplay('alice@nostr.com'));

        // Test the special "_@domain" case
        $this->assertEquals('example.com', $nip05->formatForDisplay('_@example.com'));
        $this->assertEquals('nostr.com', $nip05->formatForDisplay('_@nostr.com'));

        // Test invalid identifiers remain unchanged
        $this->assertEquals('invalid', $nip05->formatForDisplay('invalid'));
        $this->assertEquals('@example.com', $nip05->formatForDisplay('@example.com'));
    }
}
