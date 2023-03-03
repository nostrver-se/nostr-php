<?php

namespace swentel\nostr;

use Elliptic\EC;
use BitWasp\Bech32\Exception\Bech32Exception;
use function BitWasp\Bech32\convertBits;
use function BitWasp\Bech32\decode;
use function BitWasp\Bech32\encode;



class Keys
{
    /**
     * Generate private key as hex.
     *
     * @return string
     */
    public function generatePrivateKey()
    {
        $ec = new EC('secp256k1');
        $key = $ec->genKeyPair();
        $priv_hex = $key->priv->toString('hex');

        return $priv_hex;
    }

    /**
     * Generate public key from private key as hex.
     *
     * @param string $priv_hex
     *
     * @return string
     */
    public function getPublicKey($priv_hex)
    {
        $ec = new EC('secp256k1');
        $priv = $ec->keyFromPrivate($priv_hex);
        $pub_hex = $priv->getPublic(true, 'hex');

        // remove compression prefix 02 | 03
        return substr($pub_hex, 2);
    }

    /**
     * Convert a key to hex.
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
            $bytes = convertBits($data, count($data), 5, 8, FALSE);
            foreach ($bytes as $item)
            {
                $str .= str_pad(dechex($item), 2, '0', STR_PAD_LEFT);
            }
        }
        catch (Bech32Exception) {}

        return $str;
    }

    /**
     * Convert a public hex key to bech32.
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
     * Convert a private hex key to bech32.
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
     * Convert a hex key to bech32.
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
        }
        catch (Bech32Exception) {}

        return $str;
    }

}
