<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use swentel\nostr\Event\Event;
use swentel\nostr\Event\Profile\Profile;
use swentel\nostr\Nip19\Nip19Helper;

class Nip19Test extends TestCase
{
    private Nip19Helper $nip19;

    protected function setUp(): void
    {
        $this->nip19 = new Nip19Helper();
    }

    /**
     * @test
     */
    public function testDecodeEncodeNote()
    {
        $note = 'note1g0asggj90s06mmrgckk3sdu2hvkxymtt0pmep9e73zxsnx8kem2qulye77';
        $eventId = '43fb0422457c1fadec68c5ad18378abb2c626d6b787790973e888d0998f6ced4';

        // Test encoding
        $encoded = $this->nip19->encodeNote($eventId);
        $this->assertEquals($note, $encoded);
        $this->assertStringStartsWith('note', $encoded);
    }

    /**
     * @test
     */
    public function testDecodeEncodeNevent()
    {
        $nevent = 'nevent1qqsy87cyyfzhc8ada35vttgcx79tktrzd44hsausjulg3rgfnrmva4qprpmhxue69uhkummnw3ezuum9vfshxarf0qhxgetkqgsqvcu68pkfcyq5y9mz9n9u7sys33835rpnuglc6mtg7j4lv40c7ugdggh4t';
        $authorPubkey = '06639a386c9c1014217622ccbcf40908c4f1a0c33e23f8d6d68f4abf655f8f71';
        $relays = ['wss://nostr.sebastix.dev'];
        $eventId = '43fb0422457c1fadec68c5ad18378abb2c626d6b787790973e888d0998f6ced4';

        // Test decoding
        $decoded = $this->nip19->decode($nevent);
        $this->assertIsArray($decoded);
        $this->assertEquals($eventId, $decoded['event_id']);
        $this->assertEquals($relays, $decoded['relays']);

        // Test encoding
        $event = new Event();
        $event->setId($eventId);
        $event->setPublicKey($authorPubkey);
        $encoded = $this->nip19->encodeEvent($event, $relays);

        // Verify the encoded string can be decoded back
        $decodedAfterEncode = $this->nip19->decode($encoded);
        $this->assertEquals($eventId, $decodedAfterEncode['event_id']);
    }

    /**
     * @test
     */
    public function testDecodeEncodeNaddr()
    {
        $event = new Event();
        $eventId = '9e978607cca061f2c7b10ef9daa0e38672fb36c0550009d25abc82e0add48a78';
        $authorPubkey = '7f3b464b9ff3623630485060c8c3379095c8769bd33beec46c57858a57a49d99';
        $dTag = 'test-article';
        $kind = 30023;
        $relays = ['wss://nostr.sebastix.dev'];

        $event->setId($eventId);
        $event->setPublicKey($authorPubkey);
        $event->setKind($kind);

        // Test encoding
        $encoded = $this->nip19->encodeAddr($event, $dTag, $kind, $authorPubkey, $relays);

        // Test decoding
        $decoded = $this->nip19->decode($encoded);

        $this->assertIsArray($decoded);
        $this->assertEquals($dTag, $decoded['identifier']);
        $this->assertEquals($authorPubkey, $decoded['author']);
        $this->assertEquals((string) $kind, $decoded['kind']);
        $this->assertEquals($relays, $decoded['relays']);
    }

    /**
     * @test
     */
    public function testDecodeEncodeNprofile()
    {
        $pubkey = '7f3b464b9ff3623630485060c8c3379095c8769bd33beec46c57858a57a49d99';
        $relays = ['wss://nostr.sebastix.dev'];

        $profile = new Profile();
        $profile->setPublicKey($pubkey);

        // Test encoding
        $encoded = $this->nip19->encodeProfile($profile, $relays);

        // Test decoding
        $decoded = $this->nip19->decode($encoded);

        $this->assertIsArray($decoded);
        $this->assertEquals($pubkey, $decoded['pubkey']);
        $this->assertEquals($relays, $decoded['relays']);
    }

    /**
     * @test
     */
    public function testEncodeDecodeNpub()
    {
        $pubkey = '7f3b464b9ff3623630485060c8c3379095c8769bd33beec46c57858a57a49d99';

        // Test encoding
        $encoded = $this->nip19->encodeNpub($pubkey);
        $this->assertStringStartsWith('npub', $encoded);
    }

    /**
     * @test
     */
    public function testEncodeDecodeNsec()
    {
        $seckey = '7f3b464b9ff3623630485060c8c3379095c8769bd33beec46c57858a57a49d99';

        // Test encoding
        $encoded = $this->nip19->encodeNsec($seckey);
        $this->assertStringStartsWith('nsec', $encoded);
    }

    /**
     * Edge Cases - Invalid Input
     */

    /**
     * @test
     */
    public function testInvalidBech32String()
    {
        $this->expectException(\RuntimeException::class);
        $this->nip19->decode('invalid-bech32-string');
    }

    /**
     * @test
     */
    public function testTooLongBech32String()
    {
        $this->expectException(\RuntimeException::class);
        $this->nip19->decode(str_repeat('a', 91)); // Max length is 90
    }

    /**
     * @test
     */
    public function testTooShortBech32String()
    {
        $this->expectException(\RuntimeException::class);
        $this->nip19->decode('abc'); // Min length is 8
    }

    /**
     * @test
     */
    public function testInvalidPrefix()
    {
        $this->expectException(\RuntimeException::class);
        $pubkey = '7f3b464b9ff3623630485060c8c3379095c8769bd33beec46c57858a57a49d99';
        $this->nip19->encode($pubkey, 'invalid');
    }

    /**
     * @test
     */
    public function testEventWithEmptyRelays()
    {
        $event = new Event();
        $eventId = '9e978607cca061f2c7b10ef9daa0e38672fb36c0550009d25abc82e0add48a78';
        $authorPubkey = '7f3b464b9ff3623630485060c8c3379095c8769bd33beec46c57858a57a49d99';

        $event->setId($eventId);
        $event->setPublicKey($authorPubkey);

        $encoded = $this->nip19->encodeEvent($event, []);
        $decoded = $this->nip19->decode($encoded);

        $this->assertArrayHasKey('event_id', $decoded);
        $this->assertEquals($eventId, $decoded['event_id']);
    }

    /**
     * @test
     */
    public function testNaddrWithSpecialCharactersInDTag()
    {
        $event = new Event();
        $eventId = '9e978607cca061f2c7b10ef9daa0e38672fb36c0550009d25abc82e0add48a78';
        $authorPubkey = '7f3b464b9ff3623630485060c8c3379095c8769bd33beec46c57858a57a49d99';
        $dTag = 'test@article#123-_+='; // Special characters
        $kind = 30023;

        $event->setId($eventId);
        $event->setPublicKey($authorPubkey);
        $event->setKind($kind);

        $encoded = $this->nip19->encodeAddr($event, $dTag, $kind);
        $decoded = $this->nip19->decode($encoded);

        $this->assertEquals($dTag, $decoded['identifier']);
    }

    /**
     * @test
     */
    public function testEventWithMultipleRelays()
    {
        $event = new Event();
        $eventId = '9e978607cca061f2c7b10ef9daa0e38672fb36c0550009d25abc82e0add48a78';
        $authorPubkey = '7f3b464b9ff3623630485060c8c3379095c8769bd33beec46c57858a57a49d99';
        $relays = [
            'wss://relay1.example.com',
            'wss://relay2.example.com',
            'wss://relay3.example.com',
            'wss://relay4.example.com',
            'wss://relay5.example.com',
        ];

        $event->setId($eventId);
        $event->setPublicKey($authorPubkey);

        $encoded = $this->nip19->encodeEvent($event, $relays);
        $decoded = $this->nip19->decode($encoded);

        $this->assertEquals($relays, $decoded['relays']);
    }

    /**
     * @test
     */
    public function testEventWithNonStandardKind()
    {
        $event = new Event();
        $eventId = '9e978607cca061f2c7b10ef9daa0e38672fb36c0550009d25abc82e0add48a78';
        $authorPubkey = '7f3b464b9ff3623630485060c8c3379095c8769bd33beec46c57858a57a49d99';
        $kind = 99999; // Non-standard kind

        $event->setId($eventId);
        $event->setPublicKey($authorPubkey);
        $event->setKind($kind);

        $encoded = $this->nip19->encodeEvent($event, []);
        $decoded = $this->nip19->decode($encoded);

        $this->assertEquals((string) $kind, $decoded['kind']);
    }

    /**
     * @test
     */
    public function testMaximumValidInputLengths()
    {
        $event = new Event();
        $eventId = str_repeat('a', 64); // Maximum length hex string
        $authorPubkey = str_repeat('b', 64);
        $relays = ['wss://' . str_repeat('c', 80) . '.com']; // Long but valid URL

        $event->setId($eventId);
        $event->setPublicKey($authorPubkey);

        $encoded = $this->nip19->encodeEvent($event, $relays);
        $decoded = $this->nip19->decode($encoded);

        $this->assertEquals($eventId, $decoded['event_id']);
        $this->assertEquals($relays, $decoded['relays']);
    }

    /**
     * @test
     */
    public function testMalformedHexStrings()
    {
        $invalidHex = 'not-a-hex-string';
        // TODO
        // $this->expectException(\RuntimeException::class);
        // $this->nip19->encodeNote($invalidHex);
        // TODO
        // $this->expectException(\RuntimeException::class);
        // $this->nip19->encode($invalidHex, 'note');
        $this->expectException(\RuntimeException::class);
        $this->nip19->decode($invalidHex);
    }

    /**
     * @test
     */
    public function testBoundaryKindValues()
    {
        $event = new Event();
        $eventId = '9e978607cca061f2c7b10ef9daa0e38672fb36c0550009d25abc82e0add48a78';
        $authorPubkey = '7f3b464b9ff3623630485060c8c3379095c8769bd33beec46c57858a57a49d99';

        $event->setId($eventId);
        $event->setPublicKey($authorPubkey);

        // Test minimum kind value
        $event->setKind(0);
        $encoded = $this->nip19->encodeEvent($event, []);
        $decoded = $this->nip19->decode($encoded);
        $this->assertEquals('0', $decoded['kind']);

        // Test maximum kind value (32-bit unsigned int)
        $event->setKind(4294967295);
        $encoded = $this->nip19->encodeEvent($event, []);
        $decoded = $this->nip19->decode($encoded);
        $this->assertEquals('4294967295', $decoded['kind']);
    }

    /**
     * @test
     */
    public function testInvalidRelayUrls()
    {
        $event = new Event();
        $eventId = '9e978607cca061f2c7b10ef9daa0e38672fb36c0550009d25abc82e0add48a78';
        $authorPubkey = '7f3b464b9ff3623630485060c8c3379095c8769bd33beec46c57858a57a49d99';
        $invalidRelays = [
            'not-a-url',
            'http://not-wss.com', // Not WSS
            'wss:/invalid.com',    // Invalid URL format
        ];

        $event->setId($eventId);
        $event->setPublicKey($authorPubkey);

        $this->expectException(\InvalidArgumentException::class);
        $this->nip19->encodeEvent($event, $invalidRelays);
    }
}
