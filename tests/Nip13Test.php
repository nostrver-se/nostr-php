<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use swentel\nostr\Nip13\Nip13Helper;
use swentel\nostr\Event\Event;
use swentel\nostr\Key\Key;
use swentel\nostr\Sign\Sign;
use Mdanter\Ecc\Crypto\Signature\SchnorrSignature;

class Nip13Test extends TestCase
{
    /**
     * @dataProvider difficultyVectorsProvider
     */
    public function testGetPowDifficulty(string $eventId, int $expectedDifficulty): void
    {
        $this->assertSame($expectedDifficulty, Nip13Helper::getPowDifficulty($eventId));
    }

    public static function difficultyVectorsProvider(): array
    {
        return [
            // Test vectors from nostr-tools nip13.test.ts
            ['000006d8c378af1779d2feebc7603a125d99eca0ccf1085959b307f64e5dd358', 21],
            ['6bf5b4f434813c64b523d2b0e6efe18f3bd0cbbd0a5effd8ece9e00fd2531996', 1],
            ['00003479309ecdb46b1c04ce129d2709378518588bed6776e60474ebde3159ae', 18],
            ['01a76167d41add96be4959d9e618b7a35f26551d62c43c11e5e64094c6b53c83', 7],
            ['ac4f44bae06a45ebe88cfbd3c66358750159650a26c0d79e8ccaa92457fca4f6', 0],
            ['0000000000000000006cfbd3c66358750159650a26c0d79e8ccaa92457fca4f6', 73],
        ];
    }

    public function testMinePow(): void
    {
        // Create a sample event
        $event = new Event();
        $event->setPublicKey('8e0d3d3eb2881ec137a11debe736a9086715a8c8beeeda615220e2b26482b65f');
        $event->setCreatedAt(time());
        $event->setKind(1);
        $event->setTags([]);
        $event->setContent('Testing proof of work');

        // Mine with a modest difficulty target that should be reached quickly in tests
        $targetDifficulty = 10;

        // Mine the event
        $minedEvent = Nip13Helper::minePow($event, $targetDifficulty);

        // Check that a nonce tag was added
        $nonceTag = null;
        foreach ($minedEvent->getTags() as $tag) {
            if ($tag[0] === 'nonce') {
                $nonceTag = $tag;
                break;
            }
        }

        $this->assertNotNull($nonceTag, 'Nonce tag was not added to the event');
        $this->assertCount(3, $nonceTag, 'Nonce tag should have 3 elements');
        $this->assertEquals('nonce', $nonceTag[0], 'First element of nonce tag should be "nonce"');
        $this->assertEquals((string) $targetDifficulty, $nonceTag[2], 'Third element of nonce tag should be the target difficulty');


        // Verify that the event ID meets the target difficulty
        $difficulty = Nip13Helper::getPowDifficulty($minedEvent->getId());
        $this->assertGreaterThanOrEqual(
            $targetDifficulty,
            $difficulty,
            "Expected difficulty of at least $targetDifficulty, but got $difficulty",
        );
    }

    public function testMinePowSigned(): void
    {
        // Create a sample event
        $event = new Event();
        $event->setPublicKey('8e0d3d3eb2881ec137a11debe736a9086715a8c8beeeda615220e2b26482b65f');
        $event->setCreatedAt(time());
        $event->setKind(1);
        $event->setTags([]);
        $event->setContent('Testing proof of work');

        $targetDifficulty = 10;

        $minedEvent = Nip13Helper::minePow($event, $targetDifficulty);

        $private_key = new Key();
        $private_key = $private_key->generatePrivateKey();
        $signer = new Sign();
        $signer->signEvent($event, $private_key);

        // Check that a nonce tag was added
        $nonceTag = null;
        foreach ($minedEvent->getTags() as $tag) {
            if ($tag[0] === 'nonce') {
                $nonceTag = $tag;
                break;
            }
        }

        $this->assertNotNull($nonceTag, 'Nonce tag was not added to the event');
        $this->assertTrue((new SchnorrSignature())->verify($event->getPublicKey(), $event->getSignature(), $event->getId()));
        $this->assertNotNull($minedEvent->getSignature(), 'Event should be signed');
        $this->assertGreaterThanOrEqual(
            $targetDifficulty,
            Nip13Helper::getPowDifficulty($minedEvent->getId()),
            "Expected difficulty of at least $targetDifficulty, but got " . Nip13Helper::getPowDifficulty($minedEvent->getId()),
        );
    }

    public function testMinePowAfterSigned(): void
    {
        $event = new Event();
        $event->setPublicKey('8e0d3d3eb2881ec137a11debe736a9086715a8c8beeeda615220e2b26482b65f');
        $event->setCreatedAt(time());
        $event->setKind(1);


        $private_key = new Key();
        $private_key = $private_key->generatePrivateKey();
        $signer = new Sign();
        $signer->signEvent($event, $private_key);

        $targetDifficulty = 1;

        $this->expectException(RuntimeException::class);
        Nip13Helper::minePow($event, $targetDifficulty);
    }
}
