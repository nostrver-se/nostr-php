<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use swentel\nostr\Encryption\Nip44;
use swentel\nostr\Key\Key;

class Nip44VectorsTest extends TestCase
{
    /**
     * @var array The loaded test vectors from nip44.vectors.json
     */
    private array $vectors;

    /**
     * Load test vectors from JSON file
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Load the vectors from the JSON file
        $jsonPath = __DIR__ . '/../assets/nip44.vectors.json';

        if (!file_exists($jsonPath)) {
            $this->markTestSkipped("Test vector file not found: $jsonPath");
        }

        $jsonContent = file_get_contents($jsonPath);
        if ($jsonContent === false) {
            $this->markTestSkipped("Failed to read test vector file: $jsonPath");
        }

        $this->vectors = json_decode($jsonContent, true);
        if ($this->vectors === null) {
            $this->markTestSkipped("Failed to parse JSON in test vector file: $jsonPath");
        }

        // Ensure we have v2 vectors
        if (!isset($this->vectors['v2']) || !isset($this->vectors['v2']['valid'])) {
            $this->markTestSkipped("No valid v2 vectors found in the test file");
        }
    }

    /**
     * Test the padding length calculation using official vectors.
     */
    public function testPaddingLength(): void
    {
        if (!isset($this->vectors['v2']['valid']['calc_padded_len'])) {
            $this->markTestSkipped("No padding length vectors found");
        }

        $vectors = $this->vectors['v2']['valid']['calc_padded_len'];

        // Create a reflection to access the private method
        $reflectionClass = new \ReflectionClass(Nip44::class);
        $calcPaddedLen = $reflectionClass->getMethod('calcPaddedLen');
        $calcPaddedLen->setAccessible(true);

        foreach ($vectors as $vector) {
            $unpadded = $vector[0];
            $expected = $vector[1];
            $actual = $calcPaddedLen->invoke(null, $unpadded);
            $this->assertEquals($expected, $actual, "Padding calculation failed for length $unpadded");
        }
    }

    /**
     * Test conversation key derivation using official vectors.
     */
    public function testConversationKeyDerivation(): void
    {
        if (!isset($this->vectors['v2']['valid']['get_conversation_key'])) {
            $this->markTestSkipped("No conversation key vectors found");
        }

        $vectors = $this->vectors['v2']['valid']['get_conversation_key'];
        $keyGenerator = new Key();

        foreach ($vectors as $vector) {
            // If pub2 is provided, use it directly
            if (isset($vector['pub2'])) {
                $pubKey = $vector['pub2'];
            } else {
                // Otherwise, derive it from sec2
                $pubKey = $keyGenerator->getPublicKey($vector['sec2']);
            }

            $conversationKey = Nip44::getConversationKey($vector['sec1'], $pubKey);
            $this->assertEquals(
                $vector['conversation_key'],
                bin2hex($conversationKey),
                "Conversation key derivation failed for sec1={$vector['sec1']}",
            );
        }
    }

    /**
     * Test encryption and decryption with official vector examples.
     */
    public function testEncryptDecrypt(): void
    {
        if (!isset($this->vectors['v2']['valid']['encrypt_decrypt'])) {
            $this->markTestSkipped("No encrypt/decrypt vectors found");
        }

        $vectors = $this->vectors['v2']['valid']['encrypt_decrypt'];
        foreach ($vectors as $vector) {
            $conversationKey = hex2bin($vector['conversation_key']);
            $nonce = hex2bin($vector['nonce']);

            // Test encryption and decryption consistency with our implementation
            $encrypted = Nip44::encrypt($vector['plaintext'], $conversationKey, $nonce);
            $decrypted = Nip44::decrypt($encrypted, $conversationKey);
            $this->assertEquals(
                $vector['plaintext'],
                $decrypted,
                "Failed to decrypt our own encrypted message for plaintext: {$vector['plaintext']}",
            );

            // Try to decrypt the reference payload
            if (isset($vector['payload'])) {
                $decryptedReference = Nip44::decrypt($vector['payload'], $conversationKey);

                $this->assertEquals(
                    $vector['plaintext'],
                    $decryptedReference,
                    "Decrypted reference payload doesn't match expected plaintext",
                );
            }
        }
    }

    /**
     * Test error cases from the vectors
     */
    public function testErrorCases(): void
    {
        // Test with empty message (should fail)
        $conversationKey = hex2bin('c41c775356fd92eadc63ff5a0dc1da211b268cbea22316767095b2871ea1412d');

        if (isset($this->vectors['v2']['valid']['encrypt_decrypt'][0]['conversation_key'])) {
            $conversationKey = hex2bin($this->vectors['v2']['valid']['encrypt_decrypt'][0]['conversation_key']);
        }

        // Test empty message (should fail)
        try {
            Nip44::encrypt('', $conversationKey);
            $this->fail('Expected exception was not thrown for empty message');
        } catch (\Exception $e) {
            $this->addToAssertionCount(1); // Count this as a check
        }

        // Test invalid message lengths from vectors if available
        if (isset($this->vectors['v2']['invalid']['encrypt_msg_lengths'])) {
            $invalidMessages = $this->vectors['v2']['invalid']['encrypt_msg_lengths'];
            foreach ($invalidMessages as $message) {
                $messageStr = str_pad('', $message, 'a');
                $this->expectExceptionMessageMatches('/Invalid plaintext size: must be between 1 and 65535 bytes/');
                Nip44::encrypt($messageStr, $conversationKey);
            }
        }

        // Test invalid conversation keys if available
        if (isset($this->vectors['v2']['invalid']['get_conversation_key'])) {
            $invalidKeyVectors = $this->vectors['v2']['invalid']['get_conversation_key'];
            $keyGenerator = new Key();

            foreach ($invalidKeyVectors as $vector) {
                if (isset($vector['pub2'])) {
                    $pubKey = $vector['pub2'];
                } else {
                    $pubKey = $keyGenerator->getPublicKey($vector['sec2']);
                }
                Nip44::getConversationKey($vector['sec1'], $pubKey);
                $this->fail("Expected exception was not thrown for invalid key pair");
            }
        }
    }

    /**
     * Test MAC verification with tampered payloads.
     */
    public function testInvalidMac(): void
    {
        // Use a conversation key from the vectors if available
        $conversationKey = hex2bin('c41c775356fd92eadc63ff5a0dc1da211b268cbea22316767095b2871ea1412d');

        if (isset($this->vectors['v2']['valid']['encrypt_decrypt'][0]['conversation_key'])) {
            $conversationKey = hex2bin($this->vectors['v2']['valid']['encrypt_decrypt'][0]['conversation_key']);
        }

        $message = 'This is a test message';

        // Generate a valid payload
        $encrypted = Nip44::encrypt($message, $conversationKey);

        // Tamper with the MAC by changing the last few characters
        $tampered = substr($encrypted, 0, -4) . 'AAAA';

        // Should fail with invalid MAC
        $this->expectException(Exception::class);
        Nip44::decrypt($tampered, $conversationKey);
    }

    /**
     * Test symmetric communication between two users.
     */
    public function testSymmetricCommunication(): void
    {
        // Get private keys from vectors if available
        $alice_private = '0000000000000000000000000000000000000000000000000000000000000001';
        $bob_private = '0000000000000000000000000000000000000000000000000000000000000002';

        if (isset($this->vectors['v2']['valid']['encrypt_decrypt'][0]['sec1'])) {
            $alice_private = $this->vectors['v2']['valid']['encrypt_decrypt'][0]['sec1'];

            if (isset($this->vectors['v2']['valid']['encrypt_decrypt'][0]['sec2'])) {
                $bob_private = $this->vectors['v2']['valid']['encrypt_decrypt'][0]['sec2'];
            }
        }

        $keyGenerator = new Key();
        $alice_public = $keyGenerator->getPublicKey($alice_private);
        $bob_public = $keyGenerator->getPublicKey($bob_private);

        // Alice's conversation key (for sending to Bob)
        $alice_conv_key = Nip44::getConversationKey($alice_private, $bob_public);

        // Bob's conversation key (for sending to Alice)
        $bob_conv_key = Nip44::getConversationKey($bob_private, $alice_public);

        // Conversation keys should be identical
        $this->assertEquals(bin2hex($alice_conv_key), bin2hex($bob_conv_key));

        // Alice encrypts a message to Bob
        $message = 'Hello Bob, this is Alice!';
        $encrypted = Nip44::encrypt($message, $alice_conv_key);

        // Bob decrypts Alice's message
        $decrypted = Nip44::decrypt($encrypted, $bob_conv_key);
        $this->assertEquals($message, $decrypted);

        // Bob encrypts a reply to Alice
        $reply = 'Hello Alice, this is Bob!';
        $encrypted_reply = Nip44::encrypt($reply, $bob_conv_key);

        // Alice decrypts Bob's reply
        $decrypted_reply = Nip44::decrypt($encrypted_reply, $alice_conv_key);
        $this->assertEquals($reply, $decrypted_reply);
    }
}
