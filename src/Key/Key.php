<?php

declare(strict_types=1);

namespace swentel\nostr\Key;

use BitWasp\Bech32\Exception\Bech32Exception;
use Elliptic\EC;

use function BitWasp\Bech32\convertBits;
use function BitWasp\Bech32\decode;
use function BitWasp\Bech32\encode;

class Key
{
    /**
     * Generate private key as hex.
     *
     * @return string
     */
    public function generatePrivateKey(): string
    {
        $ec = new EC('secp256k1');
        $key = $ec->genKeyPair();
        return $key->priv->toString('hex');
    }

    /**
     * Generate public hex key from private hex key.
     *
     * @param string $private_hex
     *
     * @return string
     */
    public function getPublicKey(string $private_hex): string
    {
        $ec = new EC('secp256k1');
        $private_key = $ec->keyFromPrivate($private_hex);
        $public_hex = $private_key->getPublic(true, 'hex');

        // remove compression prefix 02 | 03
        return substr($public_hex, 2);
    }

    /**
     * Convert a bech32 encoded key to hex key.
     *
     * @param string $key
     *
     * @return string
     */
    public function convertToHex(string $key): string
    {
        $str = '';
        try {
            $decoded = decode($key);
            $data = $decoded[1];
            $bytes = convertBits($data, count($data), 5, 8, false);
            foreach ($bytes as $item) {
                $str .= str_pad(dechex($item), 2, '0', STR_PAD_LEFT);
            }
        } catch (Bech32Exception $e) {
            throw new \Exception($e->getMessage());
        }

        return $str;
    }

    /**
     * Convert a public hex key to a bech32 encoded string (npub).
     *
     * @param string $key
     *
     * @return string
     */
    public function convertPublicKeyToBech32(string $key): string
    {
        return $this->convertToBech32($key, 'npub');
    }

    /**
     * Convert a private hex key to bech32 encoded string (nsec).
     *
     * @param string $key
     *
     * @return string
     */
    public function convertPrivateKeyToBech32(string $key): string
    {
        return $this->convertToBech32($key, 'nsec');
    }

    /**
     * Convert a hex key to bech32 encoded string.
     *
     * @param string $key
     * @param string $prefix
     *
     * @return string
     */
    protected function convertToBech32(string $key, string $prefix): string
    {
        $str = '';

        try {
            $dec = [];
            $split = str_split($key, 2);
            foreach ($split as $item) {
                $dec[] = hexdec($item);
            }
            $bytes = convertBits($dec, count($dec), 8, 5);
            $str = encode($prefix, $bytes);
        } catch (Bech32Exception) {
        }

        return $str;
    }
}
