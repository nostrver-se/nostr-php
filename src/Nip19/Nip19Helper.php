<?php

declare(strict_types=1);

namespace swentel\nostr\Nip19;

use BitWasp\Bech32\Exception\Bech32Exception;
use swentel\nostr\Key\Key;
use swentel\nostr\Nip19\TLVEnum;

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

    public function __construct()
    {
    }

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
     * @param int $kind
     * @return string
     * @throws \Exception
     */
    public function encodeEvent(string $event_hex, array $relays = [], string $author = '', int $kind = null): string
    {
        $data = '';
        $prefix = 'nevent';
        $event_hex_in_bin = hex2bin($event_hex); // Convert hex formatted pubkey string to binary string.
        if (strlen($event_hex_in_bin) !== 32) {
            throw new \Exception(sprintf('This is an invalid event ID: %s', $event_hex));
        }
        // TODO: process TLV entries
        $tlvEntry = $this->writeTLVEntry(TLVEnum::Special, $event_hex_in_bin);
        // Optional
        if (!(empty($relays))) {
            foreach ($relays as $relay) {
                // Encode as ascii.
                //$relay = implode('', unpack('C*', $relay));
                // Alternative which requires the icon PHP extension installed on the host machine.
                // $relay = iconv('UTF-8', 'ASCII', $relay);
                // decode ascii relay string
                $tlvEntry .= $this->writeTLVEntry(TLVEnum::Relay, urlencode($relay));
            }
        }
        // Optional
        if (!(empty($author))) {
            if (strlen(hex2bin($author)) !== 32) {
                throw new \Exception(sprintf('This is an invalid author ID: %s', $event_hex));
            }
            // Convert hex formatted pubkey to 32-bit binary value.
            $tlvEntry .= $this->writeTLVEntry(TLVEnum::Author, hex2bin($author));
        }
        // Optional
        if ($kind !== null) {
            // Convert kint int to unsigned integer, big-endian.
            $v = pack('N', $kind);
            $tlvEntry .= $this->writeTLVEntry(TLVEnum::Kind, $v);
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
        $bytes = [];
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

    private function readTLVEntry(string $data, TLVEnum $type)
    {
    }

    /**
     * @param \swentel\nostr\Nip19\TLVEnum $type
     * @param string $value
     *   Binary string.
     * @return string
     */
    private function writeTLVEntry(TLVEnum $type, string $value)
    {
        // TODO
        return $value;
    }

    private function encodeTLV(Object $TLV): array
    {

        return [];
    }
}
