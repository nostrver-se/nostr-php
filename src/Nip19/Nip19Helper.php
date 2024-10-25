<?php

declare(strict_types=1);

namespace swentel\nostr\Nip19;

use BitWasp\Bech32\Exception\Bech32Exception;
use swentel\nostr\Key\Key;

use function BitWasp\Bech32\convertBits;
use function BitWasp\Bech32\encode;

/**
 * NIP-19 bech32-encoded entities
 *
 * Example reference: https://github.com/nbd-wtf/go-nostr/blob/master/nip19/nip19.go
 *
 * https://github.com/Bit-Wasp/bech32/blob/master/src/bech32.php
 */
class Nip19Helper
{
    /**
     * @var string $prefix
     */
    protected $prefix;

    public function __construct() {}

    public function decode(string $bech32string)
    {
        $length = strlen($bech32string);
        if ($length > 90) {
            throw new \Exception('Bech32 string cannot exceed 90 characters in length');
        }
        if ($length < 8) {
            throw new \Exception('Bech32 string is too short');
        }

        //        switch ($prefix) {
        //            case 'npub':
        //                break;
        //            case 'nsec':
        //                break;
        //            case 'note':
        //                break;
        //            default:
        //                throw new \Exception('Unexpected value');
        //        }
    }

    public function encode(string $value, string $prefix): string
    {
        return $this->convertToBech32($value, $prefix);
    }

    public function encodeNote(string $event_hex): string
    {
        return $this->convertToBech32($event_hex, 'note');
    }

    public function encodeEvent(string $event_hex): string
    {
        $hexInBin = hex2bin($event_hex); // Convert hex formatted string to binary string.
        if (strlen($hexInBin) !== 32) {
            throw new \Exception(sprintf('This is an invalid ID: %s', $event_hex));
        }
        // todo process TLV
        return $this->convertToBech32($event_hex, 'nevent');
    }

    public function encodeProfile(string $profile_hex): string
    {
        // todo
        return '';
    }

    public function encodeAddr(string $event_hex): string
    {
        // todo
        return '';
    }

    /**
     * @param string $pubkey
     * @return string
     */
    public function encodeNpub(string $pubkey): string
    {
        $key = new Key();
        return $key->convertPublicKeyToBech32($pubkey);
    }

    /**
     * @param string $seckey
     * @return string
     */
    public function encodeNsec(string $seckey): string
    {
        $key = new Key();
        return $key->convertPrivateKeyToBech32($seckey);
    }

    private function convertToBech32(string $key, string $prefix): string
    {
        $str = '';

        $dec = [];
        $split = str_split($key, 2);
        foreach ($split as $item) {
            $dec[] = hexdec($item);
        }
        $bytes = convertBits($dec, count($dec), 8, 5);
        $str = encode($prefix, $bytes);

        return $str;
    }

    private function readTLVEntry($value) {}

    private function writeTLVEntry($value, string $type) {}
}
