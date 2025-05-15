<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use swentel\nostr\Event\DirectMessage\DirectMessage as DirectMessageEvent;
use swentel\nostr\Key\Key;
use swentel\nostr\Nip17\DirectMessage;
use swentel\nostr\Nip59\GiftWrapService;
use swentel\nostr\Sign\Sign;

/**
 * Tests for NIP-17 Private Direct Messages.
 */
class Nip17Test extends TestCase
{
    private string $alicePrivKey;
    private string $alicePubKey;
    private string $bobPrivKey;
    private string $bobPubKey;
    private DirectMessage $directMessage;

    protected function setUp(): void
    {
        // Set up Alice and Bob's keys
        $key = new Key();
        $this->alicePrivKey = 'b540c7a54a70f53d3f898610d5139c887d18b7c7c7b3c1319fabbc9e9dc5cfb4';
        $this->alicePubKey = $key->getPublicKey($this->alicePrivKey);
        $this->bobPrivKey = '6df526548b8c03d5a749016f054e735b9b15e5dc49887086832b8c3b7a9c2cb4';
        $this->bobPubKey = $key->getPublicKey($this->bobPrivKey);

        // Set up DirectMessage with dependencies
        $sign = new Sign();
        $giftWrapService = new GiftWrapService($key, $sign);
        $this->directMessage = new DirectMessage($giftWrapService, $key);
    }

    /**
     * Test creating a direct message event
     */
    public function testDirectMessageEvent(): void
    {
        $event = new DirectMessageEvent();
        $event->setContent('Hello, world!');
        $event->addRecipient($this->bobPubKey, 'wss://relay.example.com');

        // Verify the event kind
        $this->assertEquals(14, $event->getKind());

        // Verify the content
        $this->assertEquals('Hello, world!', $event->getContent());

        // Verify the recipient tag
        $tags = $event->getTags();
        $this->assertCount(1, $tags);
        $this->assertEquals('p', $tags[0][0]);
        $this->assertEquals($this->bobPubKey, $tags[0][1]);
        $this->assertEquals('wss://relay.example.com', $tags[0][2]);
    }

    /**
     * Test creating a reply direct message event
     */
    public function testDirectMessageReply(): void
    {
        $originalEventId = '1234567890abcdef1234567890abcdef1234567890abcdef1234567890abcdef';

        $event = new DirectMessageEvent();
        $event->setContent('This is a reply');
        $event->addRecipient($this->bobPubKey);
        $event->setAsReplyTo($originalEventId, 'wss://relay.example.com');

        // Verify the tags
        $tags = $event->getTags();
        $this->assertCount(2, $tags);

        // Find and verify the e tag
        $eTagFound = false;
        foreach ($tags as $tag) {
            if ($tag[0] === 'e') {
                $eTagFound = true;
                $this->assertEquals($originalEventId, $tag[1]);
                $this->assertEquals('wss://relay.example.com', $tag[2]);
            }
        }
        $this->assertTrue($eTagFound, 'No e tag found in the event tags');
    }

    /**
     * Test sending a direct message which creates two gift wraps
     */
    public function testSendDirectMessage(): void
    {
        $message = 'Hello Bob, this is a private message!';

        // Send the direct message from Alice to Bob
        $result = $this->directMessage->sendDirectMessage(
            $this->alicePrivKey,
            $this->bobPubKey,
            $message,
        );

        // Verify the structure of the result
        $this->assertArrayHasKey('receiver', $result);
        $this->assertArrayHasKey('sender', $result);
        $this->assertArrayHasKey('receiver_relays', $result);
        $this->assertArrayHasKey('sender_relays', $result);

        // Verify the gift wraps
        $this->assertEquals(1059, $result['receiver']->getKind());
        $this->assertEquals(1059, $result['sender']->getKind());

        // Verify the receiver gift wrap has Bob's pubkey in p tag
        $receiverTags = $result['receiver']->getTags();
        $receiverPFound = false;
        foreach ($receiverTags as $tag) {
            if ($tag[0] === 'p' && $tag[1] === $this->bobPubKey) {
                $receiverPFound = true;
                break;
            }
        }
        $this->assertTrue($receiverPFound, 'Receiver gift wrap does not have correct p tag');

        // Verify the sender gift wrap has Alice's pubkey in p tag
        $senderTags = $result['sender']->getTags();
        $senderPFound = false;
        foreach ($senderTags as $tag) {
            if ($tag[0] === 'p' && $tag[1] === $this->alicePubKey) {
                $senderPFound = true;
                break;
            }
        }
        $this->assertTrue($senderPFound, 'Sender gift wrap does not have correct p tag');

        // Verify both gift wraps are signed
        $this->assertNotEmpty($result['receiver']->getSignature());
        $this->assertNotEmpty($result['sender']->getSignature());
    }

    /**
     * Test creating a direct message with additional tags
     */
    public function testDirectMessageWithAdditionalTags(): void
    {
        $event = new DirectMessageEvent();
        $event->setContent('Hello with tags!');
        $event->addRecipient($this->bobPubKey);

        // Add a custom tag
        $event->addTag(['q', '1234567890abcdef1234567890abcdef1234567890abcdef1234567890abcdef', 'wss://relay.example.com']);

        // Verify the tags
        $tags = $event->getTags();
        $this->assertCount(2, $tags);

        // Find and verify the q tag
        $qTagFound = false;
        foreach ($tags as $tag) {
            if ($tag[0] === 'q') {
                $qTagFound = true;
                $this->assertEquals('1234567890abcdef1234567890abcdef1234567890abcdef1234567890abcdef', $tag[1]);
                $this->assertEquals('wss://relay.example.com', $tag[2]);
            }
        }
        $this->assertTrue($qTagFound, 'No q tag found in the event tags');
    }

    /**
     * Test decrypting a direct message with proper seal and gift wrap as per NIP-59
     */
    public function testDecryptDirectMessage(): void
    {
        $message = 'Hello Bob, this is a sealed private message!';

        // Send the direct message from Alice to Bob (now always includes a seal)
        $result = $this->directMessage->sendDirectMessage(
            $this->alicePrivKey,
            $this->bobPubKey,
            $message,
        );

        // Verify that the result includes a seal component
        $this->assertArrayHasKey('seal', $result);
        $this->assertInstanceOf('swentel\nostr\EventInterface', $result['seal']);

        // The gift wrap should be signed with a random key, not Alice's key
        $this->assertNotEquals(
            $this->alicePubKey,
            $result['receiver']->getPublicKey(),
            'Gift wrap should be signed with a random key, not sender\'s key',
        );

        // Now use the static decryptDirectMessage method to decrypt the message
        $key = new Key();
        $decryptedEvent = DirectMessage::decryptDirectMessage(
            $result['receiver'],
            $this->bobPrivKey,
            true,
        );

        // Verify the decrypted content
        $this->assertIsArray($decryptedEvent);
        $this->assertEquals($message, $decryptedEvent['content']);
        $this->assertEquals(14, $decryptedEvent['kind']); // Verify it's a direct message event

        // Verify the decrypted message has Bob's pubkey in a p tag
        $pTagFound = false;
        foreach ($decryptedEvent['tags'] as $tag) {
            if ($tag[0] === 'p' && $tag[1] === $this->bobPubKey) {
                $pTagFound = true;
                break;
            }
        }
        $this->assertTrue($pTagFound, 'Decrypted message does not contain the recipient pubkey in p tag');
    }

    /**
     * Test the complete message flow between two users with sealed messages
     */
    public function testCompleteMessageFlow(): void
    {
        // Create a direct message helper explicitly for these tests to clearly see what we're testing
        $key = new Key();
        $sign = new Sign();
        $giftWrapService = new GiftWrapService($key, $sign);
        $directMessage = new DirectMessage($giftWrapService, $key);

        $aliceMessage = 'Hello Bob, this is a sealed private message from Alice!';

        // Step 1: Alice sends a message to Bob
        $aliceResult = $directMessage->sendDirectMessage(
            $this->alicePrivKey,
            $this->bobPubKey,
            $aliceMessage,
        );

        // Verify gift wrap is signed with a random key
        $this->assertNotEquals($this->alicePubKey, $aliceResult['receiver']->getPublicKey());

        // Step 2: Bob decrypts Alice's message
        $decryptedAliceMessage = DirectMessage::decryptDirectMessage(
            $aliceResult['receiver'],
            $this->bobPrivKey,
            true,
        );

        // Verify Bob can read Alice's message
        $this->assertIsArray($decryptedAliceMessage);
        $this->assertEquals($aliceMessage, $decryptedAliceMessage['content']);

        // Step 3: Bob replies to Alice
        $bobReply = 'Hello Alice, I got your message and am replying!';
        $bobResult = $directMessage->sendDirectMessage(
            $this->bobPrivKey,
            $this->alicePubKey,
            $bobReply,
        );

        // Verify gift wrap is signed with a random key
        $this->assertNotEquals($this->bobPubKey, $bobResult['receiver']->getPublicKey());

        // Step 4: Alice decrypts Bob's reply
        $decryptedBobReply = DirectMessage::decryptDirectMessage(
            $bobResult['receiver'],
            $this->alicePrivKey,
            true,
        );

        // Verify Alice can read Bob's reply
        $this->assertIsArray($decryptedBobReply);
        $this->assertEquals($bobReply, $decryptedBobReply['content']);
        $this->assertEquals(14, $decryptedBobReply['kind']); // Verify it's a direct message

        // Verify the decrypted message has Alice's pubkey in a p tag
        $pTagFound = false;
        foreach ($decryptedBobReply['tags'] as $tag) {
            if ($tag[0] === 'p' && $tag[1] === $this->alicePubKey) {
                $pTagFound = true;
                break;
            }
        }
        $this->assertTrue($pTagFound, 'Decrypted message should have recipient pubkey in p tag');
    }
}
