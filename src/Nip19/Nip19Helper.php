<?php

declare(strict_types=1);

namespace swentel\nostr\Nip19;

use BitWasp\Bech32\Exception\Bech32Exception;
use swentel\nostr\Key\Key;
use swentel\nostr\Nip19\TLVEnum;

use function BitWasp\Bech32\convertBits;
use function BitWasp\Bech32\decode;
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

    /**
     * @param string $event_hex
     * @param array $relays
     * @param string $author
     * @param int|null $kind
     * @return string
     */
    public function encodeEvent(string $event_hex, array $relays = [], string $author = '', int $kind = null): string
    {
        $data = '';
        $prefix = 'nevent';
        // TODO: process TLV entries
        $tlvEntry = $this->writeTLVEntry($prefix, TLVEnum::Special, $event_hex);
        // Optional
        if (!(empty($relays))) {
            foreach ($relays as $relay) {
                // Encode as ascii.
                //$relay = implode('', unpack('C*', $relay));
                // Alternative which requires the icon PHP extension installed on the host machine.
                // $relay = iconv('UTF-8', 'ASCII', $relay);
                // decode ascii relay string
                $tlvEntry .= $this->writeTLVEntry($prefix, TLVEnum::Relay, urlencode($relay));
            }
        }
        // Optional
        if (!(empty($author))) {
            if (str_starts_with($author, 'npub') === true) {
                $author = $this->convertToHex($author);
            }
            if (strlen(hex2bin($author)) !== 32) {
                throw new \RuntimeException(sprintf('This is an invalid author ID: %s', $event_hex));
            }
            // Convert hex formatted pubkey to 32-bit binary value.
            $tlvEntry .= $this->writeTLVEntry($prefix, TLVEnum::Author, $author);
        }
        // Optional
        if ($kind !== null) {
            // Convert kint int to unsigned integer, big-endian.
            $tlvEntry .= $this->writeTLVEntry($prefix, TLVEnum::Kind, $kind);
        }
        $data = $tlvEntry;

        return $this->encodeBech32($data, $prefix);
    }

    public function encodeProfile(string $pubkey, array $relays = []): string
    {
        // todo
        return '';
    }

    public function encodeAddr(string $event_hex, int $kind, string $DTag, array $relays = []): string
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

    public function encodeBech32(string $value, string $prefix): string
    {
        // TODO
        $bytes = [$value];
        return encode($prefix, $bytes);
    }

    /**
     * @param string $key
     * @param string $prefix
     * @return string
     * @throws Bech32Exception
     */
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

    /**
     * Convert a bech32 encoded string to hex string.
     *
     * @param string $key
     *
     * @return string
     */
    private function convertToHex(string $key): string
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
            throw new \RuntimeException($e->getMessage());
        }

        return $str;
    }

    private function readTLVEntry(string $data, TLVEnum $type): string {}

    /**
     * https://nips.nostr.com/19#shareable-identifiers-with-extra-metadata
     *
     * @param string $prefix
     * @param \swentel\nostr\Nip19\TLVEnum $type
     * @param string|int $value
     * @return string
     */
    private function writeTLVEntry(string $prefix, TLVEnum $type, string|int $value): string
    {
        $buf = '';
        try {
            if ($prefix === 'nevent' && $type->name === 'Special') {
                $event_hex_in_bin = hex2bin($value);
                if (strlen($event_hex_in_bin) !== 32) {
                    throw new \RuntimeException(sprintf('This is an invalid event ID: %s', $value));
                }
                // TODO Return the 32 bytes of the event id.
                $byte_array = unpack('C*', $event_hex_in_bin);
                $uint32 = $this->uInt32($value, null);
                $buf .= $uint32;
                //print $event_hex_in_bin;
            }
            if ($prefix === 'nevent' && $type->name === 'Author') {
                // TODO Return the 32 bytes of the pubkey of the event
                $buf .= $this->uInt32($value, null);
            }
            if ($prefix === 'nevent' && $type->name === 'Relay') {
                // TODO encoded as ascii
                $buf .= $value;
            }
            if ($prefix === 'nevent' && $type->name === 'Kind') {
                // TODO Return the 32-bit unsigned integer of the kind, big-endian
                $buf .= $this->uInt32($value, true);
            }

            if ($prefix === 'profile') {

            }
            if ($prefix === 'naddr') {

            }

        } catch (Bech32Exception $e) {
            throw new \RuntimeException($e->getMessage());
        }
        return $buf;
    }

    private function encodeTLV(Object $TLV): array
    {
        // TODO
        return [];
    }

    /**
     * @param $i
     * @return mixed|string
     */
    private static function uInt8($i)
    {
        return is_int($i) ? pack("C", $i) : unpack("C", $i)[1];
    }

    /**
     * @param $i
     * @param $endianness
     * @return mixed
     */
    private static function uInt16($i, $endianness = false)
    {
        $f = is_int($i) ? "pack" : "unpack";

        if ($endianness === true) {  // big-endian
            $i = $f("n", $i);
        } elseif ($endianness === false) {  // little-endian
            $i = $f("v", $i);
        } elseif ($endianness === null) {  // machine byte order
            $i = $f("S", $i);
        }

        return is_array($i) ? $i[1] : $i;
    }

    private static function uInt32($i, $endianness = false)
    {
        $f = is_int($i) ? "pack" : "unpack";

        if ($endianness === true) {  // big-endian
            $i = $f("N", $i);
        } elseif ($endianness === false) {  // little-endian
            $i = $f("V", $i);
        } elseif ($endianness === null) {  // machine byte order
            $i = $f("L", $i);
        }

        return is_array($i) ? $i[1] : $i;
    }
}
