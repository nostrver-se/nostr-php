<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use swentel\nostr\Encryption\Nip04;
use swentel\nostr\Encryption\Nip44;
use swentel\nostr\Key\Key;

class EncryptionTest extends TestCase
{
    private string $alicePrivKey;
    private string $alicePubKey;
    private string $bobPrivKey;
    private string $bobPubKey;
    private string $charliePrivKey;
    private string $charliePubKey;
    private Key $keyGenerator;

    protected function setUp(): void
    {
        $this->keyGenerator = new Key();

        // Generate test keys using the Key class
        $this->alicePrivKey = $this->keyGenerator->generatePrivateKey();
        $this->alicePubKey = $this->keyGenerator->getPublicKey($this->alicePrivKey);

        $this->bobPrivKey = $this->keyGenerator->generatePrivateKey();
        $this->bobPubKey = $this->keyGenerator->getPublicKey($this->bobPrivKey);

        $this->charliePrivKey = $this->keyGenerator->generatePrivateKey();
        $this->charliePubKey = $this->keyGenerator->getPublicKey($this->charliePrivKey);
    }

    public function testKeyConversion(): void
    {
        // Test that keys can be converted to bech32 and back
        $npub = $this->keyGenerator->convertPublicKeyToBech32($this->alicePubKey);
        $nsec = $this->keyGenerator->convertPrivateKeyToBech32($this->alicePrivKey);

        $this->assertStringStartsWith('npub', $npub);
        $this->assertStringStartsWith('nsec', $nsec);

        $hexPub = $this->keyGenerator->convertToHex($npub);
        $hexPriv = $this->keyGenerator->convertToHex($nsec);

        $this->assertEquals($this->alicePubKey, $hexPub);
        $this->assertEquals($this->alicePrivKey, $hexPriv);
    }

    public function testNip04Encryption(): void
    {
        $message = "Hello, this is a secret message!";

        // Alice encrypts a message for Bob
        $encrypted = Nip04::encrypt($message, $this->alicePrivKey, $this->bobPubKey);

        // Bob decrypts the message
        $decrypted = Nip04::decrypt($encrypted, $this->bobPrivKey, $this->alicePubKey);

        $this->assertEquals($message, $decrypted);

        // Verify that Charlie cannot decrypt the message
        $this->expectException(Exception::class);
        Nip04::decrypt($encrypted, $this->charliePrivKey, $this->alicePubKey);
    }

    public function testNip44Encryption(): void
    {
        $message = "Hello, this is a secret message!";

        // Get conversation key from Alice and Bob's keys
        $conversationKey = Nip44::getConversationKey($this->alicePrivKey, $this->bobPubKey);

        // Alice encrypts a message
        $encrypted = Nip44::encrypt($message, $conversationKey);

        // Verify the encrypted format
        $decoded = base64_decode($encrypted);
        $this->assertEquals(2, ord($decoded[0])); // Version byte
        $this->assertGreaterThan(65, strlen($decoded)); // Version + nonce + min padded size + MAC

        // Bob gets the same conversation key and decrypts
        $bobConversationKey = Nip44::getConversationKey($this->bobPrivKey, $this->alicePubKey);
        $this->assertEquals($conversationKey, $bobConversationKey);

        $decrypted = Nip44::decrypt($encrypted, $bobConversationKey);
        $this->assertEquals($message, $decrypted);

        // Verify that wrong keys fail to decrypt
        $wrongKey = Nip44::getConversationKey($this->charliePrivKey, $this->alicePubKey);
        $this->expectException(Exception::class);
        Nip44::decrypt($encrypted, $wrongKey);
    }

    public function testNip44PaddingAndLimits(): void
    {
        // Test minimum size
        $shortMessage = "x";
        $conversationKey = Nip44::getConversationKey($this->alicePrivKey, $this->bobPubKey);
        $encrypted = Nip44::encrypt($shortMessage, $conversationKey);
        $decrypted = Nip44::decrypt($encrypted, $conversationKey);
        $this->assertEquals($shortMessage, $decrypted);

        // Test empty message (should fail)
        $this->expectException(Exception::class);
        Nip44::encrypt("", $conversationKey);
    }

    public function testNip44MessageAuthentication(): void
    {
        $message = "Hello, this is a secret message!";
        $conversationKey = Nip44::getConversationKey($this->alicePrivKey, $this->bobPubKey);

        // Encrypt with a known nonce for reproducible test
        $nonce = str_repeat("\x00", 32);
        $encrypted = Nip44::encrypt($message, $conversationKey, $nonce);

        // Tamper with the ciphertext
        $decoded = base64_decode($encrypted);
        $tampered = substr($decoded, 0, 40) . chr(ord($decoded[40]) ^ 1) . substr($decoded, 41);

        // Verify that tampered message fails MAC check
        $this->expectException(Exception::class);
        Nip44::decrypt(base64_encode($tampered), $conversationKey);
    }

    public function testNip04WithBech32Keys(): void
    {
        $message = "Hello, this is a bech32 key test!";

        // Convert keys to bech32 format
        $aliceNsec = $this->keyGenerator->convertPrivateKeyToBech32($this->alicePrivKey);
        $bobNpub = $this->keyGenerator->convertPublicKeyToBech32($this->bobPubKey);

        // Alice encrypts a message for Bob using bech32 keys
        $encrypted = Nip04::encrypt($message, $aliceNsec, $bobNpub);

        // Bob decrypts the message using bech32 keys
        $bobNsec = $this->keyGenerator->convertPrivateKeyToBech32($this->bobPrivKey);
        $aliceNpub = $this->keyGenerator->convertPublicKeyToBech32($this->alicePubKey);
        $decrypted = Nip04::decrypt($encrypted, $bobNsec, $aliceNpub);

        $this->assertEquals($message, $decrypted);

        // Test mixed format (hex private key, bech32 public key)
        $mixedEncrypted = Nip04::encrypt($message, $this->alicePrivKey, $bobNpub);
        $mixedDecrypted = Nip04::decrypt($mixedEncrypted, $bobNsec, $this->alicePubKey);
        $this->assertEquals($message, $mixedDecrypted);
    }

    public function testNip44WithBech32Keys(): void
    {
        $message = "Hello, this is a bech32 key test for NIP-44!";

        // Convert keys to bech32 format
        $aliceNsec = $this->keyGenerator->convertPrivateKeyToBech32($this->alicePrivKey);
        $bobNpub = $this->keyGenerator->convertPublicKeyToBech32($this->bobPubKey);

        // Get conversation key using bech32 keys
        $conversationKey = Nip44::getConversationKey($aliceNsec, $bobNpub);

        // Alice encrypts a message
        $encrypted = Nip44::encrypt($message, $conversationKey);

        // Bob gets the same conversation key using bech32 keys and decrypts
        $bobNsec = $this->keyGenerator->convertPrivateKeyToBech32($this->bobPrivKey);
        $aliceNpub = $this->keyGenerator->convertPublicKeyToBech32($this->alicePubKey);
        $bobConversationKey = Nip44::getConversationKey($bobNsec, $aliceNpub);

        $this->assertEquals($conversationKey, $bobConversationKey);
        $decrypted = Nip44::decrypt($encrypted, $bobConversationKey);
        $this->assertEquals($message, $decrypted);

        // Test mixed format (hex private key, bech32 public key)
        $mixedConversationKey = Nip44::getConversationKey($this->alicePrivKey, $bobNpub);
        $mixedEncrypted = Nip44::encrypt($message, $mixedConversationKey);
        $mixedDecrypted = Nip44::decrypt($mixedEncrypted, $mixedConversationKey);
        $this->assertEquals($message, $mixedDecrypted);
    }
}
