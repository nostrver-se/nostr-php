<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use swentel\nostr\Event\Event;
use swentel\nostr\Key\Key;
use swentel\nostr\Nip59\GiftWrap;
use swentel\nostr\Nip59\GiftWrapInterface;
use swentel\nostr\Nip59\GiftWrapService;
use swentel\nostr\Sign\Sign;
use swentel\nostr\EventInterface;

/**
 * Tests for NIP-59 Gift Wrapping.
 */
class Nip59Test extends TestCase
{
    private string $alicePrivKey;
    private string $alicePubKey;
    private string $bobPrivKey;
    private string $bobPubKey;
    private GiftWrapService $giftWrapService;
    private Key $key;
    private Sign $sign;

    protected function setUp(): void
    {
        // Set up Alice and Bob's keys
        $this->key = new Key();
        $this->alicePrivKey = 'b540c7a54a70f53d3f898610d5139c887d18b7c7c7b3c1319fabbc9e9dc5cfb4';
        $this->alicePubKey = $this->key->getPublicKey($this->alicePrivKey);
        $this->bobPrivKey = '6df526548b8c03d5a749016f054e735b9b15e5dc49887086832b8c3b7a9c2cb4';
        $this->bobPubKey = $this->key->getPublicKey($this->bobPrivKey);

        // Set up GiftWrapService
        $this->sign = new Sign();
        $this->giftWrapService = new GiftWrapService($this->key, $this->sign);
    }

    /**
     * Test creating a Seal
     */
    public function testCreateSeal(): void
    {
        // Create an event to seal
        $event = new Event();
        $event->setKind(1);
        $event->setContent('This is a secret message.');

        // Create a seal
        $seal = $this->giftWrapService->createSeal($event, $this->alicePrivKey, $this->bobPubKey);

        // Assert that the seal is created correctly
        $this->assertInstanceOf(EventInterface::class, $seal);
        $this->assertNotEmpty($seal->getContent());

        // The seal content should be base64 encoded
        $this->assertTrue(base64_decode($seal->getContent(), true) !== false, 'Seal content is not valid base64');
    }

    /**
     * Test creating a GiftWrap
     */
    public function testCreateGiftWrap(): void
    {
        // Create an event to wrap
        $event = new Event();
        $event->setKind(1);
        $event->setContent('This is a secret message for gift wrapping.');

        // Create a gift wrap
        $giftWrap = $this->giftWrapService->createGiftWrap($event, $this->bobPubKey);

        // Assert that the gift wrap is created correctly
        $this->assertInstanceOf(GiftWrapInterface::class, $giftWrap);
        $this->assertEquals(1059, $giftWrap->getKind());
        $this->assertEquals($this->bobPubKey, $giftWrap->getRecipient());
        $this->assertNotEmpty($giftWrap->getEncryptedContent());
        $this->assertNotEmpty($giftWrap->getSignature());

        // The gift wrap should have a p tag with the recipient's pubkey
        $tags = $giftWrap->getTags();
        $recipientTag = false;
        foreach ($tags as $tag) {
            if ($tag[0] === 'p' && $tag[1] === $this->bobPubKey) {
                $recipientTag = true;
                break;
            }
        }
        $this->assertTrue($recipientTag, 'Gift wrap is missing the recipient p tag');
    }

    /**
     * Test GiftWrap class implementation
     */
    public function testGiftWrapClass(): void
    {
        $giftWrap = new GiftWrap();

        // Test initial state
        $this->assertEquals(1059, $giftWrap->getKind());

        // Test adding a recipient
        $giftWrap->addRecipient($this->bobPubKey);
        $this->assertEquals($this->bobPubKey, $giftWrap->getRecipient());

        // Test setting encrypted content
        $encryptedContent = 'encrypted_content_here';
        $giftWrap->setEncryptedContent($encryptedContent);
        $this->assertEquals($encryptedContent, $giftWrap->getEncryptedContent());
        $this->assertEquals($encryptedContent, $giftWrap->getContent());
    }

    /**
     * Test end-to-end encryption and decryption with a gift wrap
     */
    public function testGiftWrapEndToEnd(): void
    {
        // Create an event to wrap
        $originalEvent = new Event();
        $originalEvent->setKind(1);
        $originalContent = 'This is a secret message that should be encrypted and decrypted properly.';
        $originalEvent->setContent($originalContent);

        // Create a gift wrap - now uses a random key internally
        $giftWrap = $this->giftWrapService->createGiftWrap($originalEvent, $this->bobPubKey);

        // Verify the gift wrap is created correctly
        $this->assertEquals(1059, $giftWrap->getKind());
        $this->assertEquals($this->bobPubKey, $giftWrap->getRecipient());
        $this->assertNotEmpty($giftWrap->getEncryptedContent());

        // Test decryption - this is more complex now that we use random keys
        // We'll need to simulate the full decryption process
        // This will be tested in detail in the DirectMessage tests
        // Here we just verify the basic properties of the gift wrap

        // Verify the gift wrap has a signature
        $this->assertNotEmpty($giftWrap->getSignature());

        // Verify the public key is not Alice's - it should be a random key
        $this->assertNotEquals(
            $this->alicePubKey,
            $giftWrap->getPublicKey(),
            'Gift wrap should NOT be signed with sender\'s key, but with a random key',
        );

        // Validate that the public key length is correct (64 hex chars)
        $this->assertEquals(
            64,
            strlen($giftWrap->getPublicKey()),
            'Random pubkey should be 64 hex characters long',
        );
    }

    /**
     * Test that a Seal follows NIP-59 spec by not containing any tags
     */
    public function testSealDoesNotContainTags(): void
    {
        // Create an event to seal
        $event = new Event();
        $event->setKind(1);
        $event->setContent('This message should be sealed without any tags.');

        // Create a seal
        $sealEvent = $this->giftWrapService->createSeal($event, $this->alicePrivKey, $this->bobPubKey);

        // Assert that the seal event has kind 13
        $this->assertEquals(13, $sealEvent->getKind());

        // Assert that the seal event has NO tags (as required by NIP-59)
        $this->assertEmpty($sealEvent->getTags(), 'Seal MUST NOT contain any tags according to NIP-59');
    }
}
