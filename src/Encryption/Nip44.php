<?php

declare(strict_types=1);

namespace swentel\nostr\Encryption;

use Elliptic\EC;
use Exception;
use swentel\nostr\Key\Key;
use ChaCha20\Cipher;

/**
 * NIP-44 encryption implementation.
 * Based on the reference implementation from nostr-tools.
 */
class Nip44
{
    private const VERSION = 2;
    private const MIN_PLAINTEXT_SIZE = 1;
    private const MAX_PLAINTEXT_SIZE = 0xffff;
    private const HEADER_SIZE = 1 + 32; // 1 byte version + 32 bytes nonce
    private const HMAC_SIZE = 32;
    private const MIN_PADDING_SIZE = 32;

    /**
     * Get conversation key using HKDF with shared secret.
     */
    public static function getConversationKey(string $privateKey, string $publicKey): string
    {
        // Convert keys from bech32 format if needed
        $key = new Key();
        if (str_starts_with($privateKey, 'nsec') === true) {
            $privateKey = $key->convertToHex($privateKey);
        }
        if (str_starts_with($publicKey, 'npub') === true) {
            $publicKey = $key->convertToHex($publicKey);
        }

        $ec = new EC('secp256k1');
        $private = $ec->keyFromPrivate($privateKey);
        $public = $ec->keyFromPublic('02' . $publicKey, 'hex');
        $shared = $private->derive($public->getPublic());

        // Get only the X coordinate (32 bytes)
        $sharedX = hex2bin(str_pad(substr($shared->toString(16), 0, 64), 64, '0', STR_PAD_LEFT));

        // HKDF extract with salt 'nip44-v2'
        return hash_hmac('sha256', $sharedX, 'nip44-v2', true);
    }

    /**
     * Get message keys using HKDF expansion.
     */
    private static function getMessageKeys(string $conversationKey, string $nonce): array
    {
        // Validate input lengths
        if (strlen($conversationKey) !== 32) {
            throw new Exception('Conversation key must be 32 bytes');
        }

        if (strlen($nonce) !== 32) {
            throw new Exception('Nonce must be 32 bytes');
        }

        // Use expand to get exactly 76 bytes of key material
        $expanded = self::hkdfExpand($conversationKey, $nonce, 76);

        // Split the expanded material into keys
        return [
            'chacha_key' => substr($expanded, 0, 32),
            'chacha_nonce' => substr($expanded, 32, 12),
            'hmac_key' => substr($expanded, 44, 32),
        ];
    }

    /**
    * HKDF-Expand implementation.
    * Port of the Go implementation's hkdf.Expand().
    */
    private static function hkdfExpand(string $prk, string $info, int $length): string
    {
        $hashLen = self::HMAC_SIZE; // SHA-256 hash length
        $result = '';
        $t = '';

        // Calculate how many blocks we need
        $blocks = ceil($length / $hashLen);

        // Generate each block
        for ($i = 1; $i <= $blocks; $i++) {
            // T(i) = HMAC-Hash(PRK, T(i-1) | info | i)
            $data = $t . $info . chr($i);
            $t = hash_hmac('sha256', $data, $prk, true);
            $result .= $t;
        }

        // Return only the requested length
        return substr($result, 0, $length);
    }

    /**
     * Calculate padded length.
     */
    private static function calcPaddedLen(int $len): int
    {
        if ($len <= 0) {
            throw new Exception('Expected positive integer');
        }

        if ($len <= self::MIN_PADDING_SIZE) {
            return self::MIN_PADDING_SIZE;
        }

        $nextPower = pow(2, floor(log($len - 1, 2)) + 1);
        $chunk = $nextPower <= 256 ? self::MIN_PADDING_SIZE : (int) ($nextPower / 8);

        return $chunk * (int) (floor(($len - 1) / $chunk) + 1);
    }

    /**
     * Pad the plaintext according to NIP-44 spec.
     */
    private static function pad(string $plaintext): string
    {
        $bytes = mb_convert_encoding($plaintext, 'UTF-8');
        $len = strlen($bytes);

        if ($len < self::MIN_PLAINTEXT_SIZE || $len > self::MAX_PLAINTEXT_SIZE) {
            throw new Exception('Invalid plaintext size: must be between 1 and 65535 bytes');
        }

        // Write length as big-endian uint16
        $prefix = pack('n', $len);

        // Add zero padding
        $paddedLen = self::calcPaddedLen($len);
        $padding = str_repeat("\0", $paddedLen - $len);

        return $prefix . $bytes . $padding;
    }

    /**
     * Unpad the decrypted data according to NIP-44 spec.
     */
    private static function unpad(string $padded): string
    {
        // Read length as big-endian uint16
        $unpaddedLen = unpack('n', substr($padded, 0, 2))[1];
        $unpadded = substr($padded, 2, $unpaddedLen);

        if ($unpaddedLen < self::MIN_PLAINTEXT_SIZE ||
            $unpaddedLen > self::MAX_PLAINTEXT_SIZE ||
            strlen($unpadded) !== $unpaddedLen ||
            strlen($padded) !== 2 + self::calcPaddedLen($unpaddedLen)
        ) {
            throw new Exception('Invalid padding');
        }

        return $unpadded;
    }

    /**
     * Calculate HMAC for the message and AAD.
     */
    private static function hmacAad(string $key, string $message, string $aad): string
    {
        if (strlen($aad) !== self::HMAC_SIZE) {
            throw new Exception('AAD associated data must be 32 bytes');
        }

        return hash_hmac('sha256', $aad . $message, $key, true);
    }

    /**
     * Encrypt a message using NIP-44.
     */
    public static function encrypt(string $plaintext, string $conversationKey, ?string $nonce = null): string
    {
        $nonce = $nonce ?? random_bytes(32);
        $keys = self::getMessageKeys($conversationKey, $nonce);

        $padded = self::pad($plaintext);

        $chacha20 = new Cipher();
        $ctx = $chacha20->init($keys['chacha_key'], $keys['chacha_nonce']);
        $ciphertext = $chacha20->encrypt($ctx, $padded);


        // Calculate MAC
        $mac = self::hmacAad($keys['hmac_key'], $ciphertext, $nonce);

        // Combine version, nonce, ciphertext, and MAC
        $payload = chr(self::VERSION) . $nonce . $ciphertext . $mac;

        return base64_encode($payload);
    }

    /**
     * Decrypt a message using NIP-44.
     */
    public static function decrypt(string $payload, string $conversationKey): string
    {
        $data = base64_decode($payload);
        if ($data === false) {
            throw new Exception('Invalid base64');
        }

        $minSize = self::HEADER_SIZE + self::MIN_PADDING_SIZE + self::HMAC_SIZE;
        if (strlen($data) < $minSize) {
            throw new Exception('Invalid payload size');
        }

        $version = ord($data[0]);
        if ($version !== self::VERSION) {
            throw new Exception('Unknown encryption version ' . $version);
        }

        $nonce = substr($data, 1, 32);
        $ciphertext = substr($data, self::HEADER_SIZE, -self::HMAC_SIZE);
        $mac = substr($data, -self::HMAC_SIZE);

        $keys = self::getMessageKeys($conversationKey, $nonce);

        // Verify MAC
        $calculatedMac = self::hmacAad($keys['hmac_key'], $ciphertext, $nonce);
        if (!hash_equals($calculatedMac, $mac)) {
            throw new Exception('Invalid MAC');
        }

        // Decrypt using ChaCha20
        $chacha20 = new Cipher();
        $ctx = $chacha20->init($keys['chacha_key'], $keys['chacha_nonce']);
        $paddedPlaintext = $chacha20->decrypt($ctx, $ciphertext);

        return self::unpad($paddedPlaintext);
    }
}
