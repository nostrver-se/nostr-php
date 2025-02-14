<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use swentel\nostr\Event\Event;
use swentel\nostr\Key\Key;
use swentel\nostr\Sign\Sign;

class FromVerifiedTest extends TestCase
{
    private string $privateKey;
    private string $publicKey;
    private array $validEventData;
    private string $validEventJson;

    protected function setUp(): void
    {
        // Generate test keys
        $keyGenerator = new Key();
        $this->privateKey = $keyGenerator->generatePrivateKey();
        $this->publicKey = $keyGenerator->getPublicKey($this->privateKey);

        // Create a valid event
        $event = new Event();
        $event->setPublicKey($this->publicKey)
            ->setCreatedAt(time())
            ->setKind(1)
            ->setContent('Test message')
            ->setTags([['t', 'test']]);

        // Sign the event
        $signer = new Sign();
        $signer->signEvent($event, $this->privateKey);

        // Store the event data for tests
        $this->validEventData = $event->toArray();
        $this->validEventJson = $event->toJson();
    }

    public function testFromVerifiedWithValidJsonString(): void
    {
        $event = Event::fromVerified($this->validEventJson);

        $this->assertNotNull($event);
        $this->assertInstanceOf(Event::class, $event);
        $this->assertEquals($this->validEventData['pubkey'], $event->getPublicKey());
        $this->assertEquals($this->validEventData['content'], $event->getContent());
        $this->assertEquals($this->validEventData['kind'], $event->getKind());
        $this->assertEquals($this->validEventData['created_at'], $event->getCreatedAt());
        $this->assertEquals($this->validEventData['tags'], $event->getTags());
        $this->assertEquals($this->validEventData['id'], $event->getId());
        $this->assertEquals($this->validEventData['sig'], $event->getSignature());
    }

    public function testFromVerifiedWithValidObject(): void
    {
        $eventObj = json_decode($this->validEventJson);
        $event = Event::fromVerified($eventObj);

        $this->assertNotNull($event);
        $this->assertInstanceOf(Event::class, $event);
        $this->assertEquals($this->validEventData['pubkey'], $event->getPublicKey());
    }

    public function testFromVerifiedWithInvalidJson(): void
    {
        $invalidJson = '{invalid json}';
        $event = Event::fromVerified($invalidJson);

        $this->assertNull($event);
    }

    public function testFromVerifiedWithMissingFields(): void
    {
        $incompleteData = [
            'pubkey' => $this->publicKey,
            'created_at' => time(),
            'kind' => 1,
            'content' => 'Test',
            // Missing id, sig, and tags
        ];

        $event = Event::fromVerified(json_encode($incompleteData));
        $this->assertNull($event);
    }

    public function testFromVerifiedWithInvalidSignature(): void
    {
        $eventData = json_decode($this->validEventJson, true);
        $eventData['sig'] = str_repeat('0', 128); // Invalid signature

        $event = Event::fromVerified(json_encode($eventData));
        $this->assertNull($event);
    }

    public function testFromVerifiedWithInvalidId(): void
    {
        $eventData = json_decode($this->validEventJson, true);
        $eventData['id'] = str_repeat('0', 64); // Invalid ID that doesn't match content

        $event = Event::fromVerified(json_encode($eventData));
        $this->assertNull($event);
    }

    public function testFromVerifiedWithInvalidTags(): void
    {
        $eventData = json_decode($this->validEventJson, true);
        $eventData['tags'] = [['t', 123]]; // Tags must be strings

        $event = Event::fromVerified(json_encode($eventData));
        $this->assertNull($event);
    }

    public function testFromVerifiedWithInvalidTypes(): void
    {
        $eventData = json_decode($this->validEventJson, true);
        $eventData['created_at'] = '1234'; // Should be int

        $event = Event::fromVerified(json_encode($eventData));
        $this->assertNull($event);
    }
}
