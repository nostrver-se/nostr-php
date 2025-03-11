<?php

declare(strict_types=1);

namespace swentel\nostr\Encryption;

use Elliptic\EC;
use Exception;
use swentel\nostr\Key\Key;

/**
 * NIP-04 encryption implementation.
 * Based on the reference implementation from nostr-tools.
 */
class Nip04
{
    /**
     * Derive a shared secret using secp256k1.
     */
    private static function deriveSharedSecret(string $privateKey, string $publicKey): string
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
        // Add back the compression prefix (02 or 03)
        $public = $ec->keyFromPublic('02' . $publicKey, 'hex');
        $shared = $private->derive($public->getPublic());

        // Get only the X coordinate (32 bytes) as per nostr-tools implementation
        return substr($shared->toString(16), 0, 64);
    }

    /**
     * Encrypt a message using NIP-04 (AES-CBC).
     */
    public static function encrypt(string $text, string $privateKey, string $publicKey): string
    {
        $sharedSecret = self::deriveSharedSecret($privateKey, $publicKey);

        // Generate a random 16-byte IV
        $iv = random_bytes(16);

        // Encrypt using AES-CBC with PKCS7 padding
        $ciphertext = openssl_encrypt(
            $text,
            'aes-256-cbc',
            hex2bin($sharedSecret),
            OPENSSL_RAW_DATA,
            $iv,
        );

        if ($ciphertext === false) {
            throw new Exception('Encryption failed: ' . openssl_error_string());
        }

        // Format as base64(ciphertext) + "?iv=" + base64(iv)
        return base64_encode($ciphertext) . '?iv=' . base64_encode($iv);
    }

    /**
     * Decrypt a message using NIP-04 (AES-CBC).
     */
    public static function decrypt(string $ciphertext, string $privateKey, string $publicKey): string
    {
        $sharedSecret = self::deriveSharedSecret($privateKey, $publicKey);

        // Split the ciphertext and IV
        $parts = explode('?iv=', $ciphertext);
        if (count($parts) !== 2) {
            throw new Exception('Invalid ciphertext format');
        }

        $encryptedData = base64_decode($parts[0]);
        $iv = base64_decode($parts[1]);

        if ($encryptedData === false || $iv === false) {
            throw new Exception('Invalid base64 encoding');
        }

        // Decrypt using AES-CBC with PKCS7 padding
        $decrypted = openssl_decrypt(
            $encryptedData,
            'aes-256-cbc',
            hex2bin($sharedSecret),
            OPENSSL_RAW_DATA,
            $iv,
        );

        if ($decrypted === false) {
            throw new Exception('Decryption failed: ' . openssl_error_string());
        }

        return $decrypted;
    }
}
