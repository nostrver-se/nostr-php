<?php

declare(strict_types=1);

namespace swentel\nostr\Encryption;

use Elliptic\EC;
use Exception;
use ParagonIE\Sodium\Compat;
use swentel\nostr\Key\Key;

/**
 * NIP-44 encryption implementation.
 * Based on the reference implementation from nostr-tools.
 */
class Nip44
{
    private const VERSION = 2;
    private const MIN_PLAINTEXT_SIZE = 1;
    private const MAX_PLAINTEXT_SIZE = 0xffff;

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
        // HKDF expand to get 88 bytes (32 for chacha key, 24 for nonce, 32 for hmac key)
        $keys = hash_hkdf('sha256', $conversationKey, 88, $nonce, '');

        return [
            'chacha_key' => substr($keys, 0, 32),
            'chacha_nonce' => substr($keys, 32, 24),
            'hmac_key' => substr($keys, 56, 32),
        ];
    }

    /**
     * Calculate padded length.
     */
    private static function calcPaddedLen(int $len): int
    {
        if ($len <= 0) {
            throw new Exception('Expected positive integer');
        }

        if ($len <= 32) {
            return 32;
        }

        $nextPower = pow(2, floor(log($len - 1, 2)) + 1);
        $chunk = $nextPower <= 256 ? 32 : (int) ($nextPower / 8);

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
        if (strlen($aad) !== 32) {
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

        // Encrypt using ChaCha20
        $ciphertext = Compat::crypto_stream_xor(
            $padded,
            $keys['chacha_nonce'],
            $keys['chacha_key'],
        );

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

        $version = ord($data[0]);
        if ($version !== self::VERSION) {
            throw new Exception('Unknown encryption version ' . $version);
        }

        $nonce = substr($data, 1, 32);
        $ciphertext = substr($data, 33, -32);
        $mac = substr($data, -32);

        $keys = self::getMessageKeys($conversationKey, $nonce);

        // Verify MAC
        $calculatedMac = self::hmacAad($keys['hmac_key'], $ciphertext, $nonce);
        if (!hash_equals($calculatedMac, $mac)) {
            throw new Exception('Invalid MAC');
        }

        // Decrypt using ChaCha20
        $padded = Compat::crypto_stream_xor(
            $ciphertext,
            $keys['chacha_nonce'],
            $keys['chacha_key'],
        );

        return self::unpad($padded);
    }
}
