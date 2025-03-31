<?php

declare(strict_types=1);

namespace swentel\nostr\Nip19;

use BitWasp\Bech32\Exception\Bech32Exception;
use swentel\nostr\Event\Event;
use swentel\nostr\Event\Profile\Profile;
use swentel\nostr\Key\Key;

use function BitWasp\Bech32\convertBits;
use function BitWasp\Bech32\encode;

/**
 * NIP-19 bech32-encoded entities
 *
 * Example reference Go library: https://github.com/nbd-wtf/go-nostr/blob/master/nip19/nip19.go
 * Example reference Javascript library: https://github.com/nbd-wtf/nostr-tools/blob/master/nip19.ts
 *
 * https://github.com/Bit-Wasp/bech32/blob/master/src/bech32.php
 *
 * https://github.com/nostriphant/nip-19
 *
 */
class Nip19Helper
{
    /**
     * Decode an bech32 identifier into TLV / metadata.
     *
     * @param string $bech32string
     * @return array
     * @throws \Exception
     */
    public function decode(string $bech32string)
    {
        try {
            $length = strlen($bech32string);

            if ($length > Bech32::BECH32_MAX_LENGTH) {
                throw new \Exception('Bech32 string cannot exceed '.Bech32::BECH32_MAX_LENGTH.' characters in length');
            }
            if ($length < 8) {
                throw new \Exception('Bech32 string is too short');
            }
            // Find the separator (1)
            $pos = strrpos($bech32string, '1');
            if ($pos === false) {
                throw new \Exception('Invalid Bech32 string');
            }
            // Extract human-readable part (HRP)
            $prefix = substr($bech32string, 0, $pos);

            // Extract data part
            $data_part = substr($bech32string, $pos + 1);
            $data = [];
            for ($i = 0, $iMax = strlen($data_part); $i < $iMax; $i++) {
                $data[] = strpos(Bech32::BECH32_CHARSET, $data_part[$i]);
                if ($data[$i] === false) {
                    throw new \Exception('Invalid character in Bech32 string');
                }
            }
            // Convert 5-bit data to 8-bit data
            $binaryData = convertBits($data, count($data), 5, 8);
            if ($prefix === 'npub') {
                return [$prefix, $binaryData];
            }
            // Parse the binary data into TLV format
            $tlvEntries = [];
            $offset = 0;
            while ($offset < count($binaryData)) {
                if ($offset + 1 >= count($binaryData)) {
                    throw new \Exception("Incomplete TLV data");
                }
                // Read the Type (T) and Length (L)
                $type = $binaryData[$offset];
                $length = $binaryData[$offset + 1];
                $offset += 2;

                // Ensure we have enough data for the value
                if ($offset + $length > count($binaryData)) {
                    break;
                } else {
                    // Extract the Value (V)
                    $value = array_slice($binaryData, $offset, $length);
                }
                $offset += $length;
                // Add the TLV to the parsed array
                $tlvEntries[] = [
                    'type' => $type,
                    'length' => $length,
                    'value' => $value,
                ];
            }
            // Parse TLVs into readable data
            $tlvData = [];
            $typeSlug = 0; // special
            $typeRelays = 1; // relays
            $typeAuthor = 2; // author
            $typeKind = 3; // kind
            $relays = [];
            foreach ($tlvEntries as $item) {
                // The 32 bytes of the event id
                if ($item['type'] === $typeSlug && $prefix === 'nevent') {
                    $val = '';
                    foreach ($item['value'] as $byte) {
                        $val .= str_pad(dechex($byte), 2, '0', STR_PAD_LEFT);
                    }
                    $tlvData['event_id'] = $val;
                }
                // The 32 bytes of the profile public key
                if ($item['type'] === $typeSlug && $prefix === 'nprofile') {
                    $val = '';
                    foreach ($item['value'] as $byte) {
                        $val .= str_pad(dechex($byte), 2, '0', STR_PAD_LEFT);
                    }
                    $tlvData['pubkey'] = $val;
                }
                // identifier (the d-tag) of the event
                if ($item['type'] === $typeSlug && $prefix === 'naddr') {
                    $val = implode('', array_map('chr', $item['value']));
                    $tlvData['identifier'] = $val;
                }
                if ($item['type'] === $typeRelays) {
                    $relays[] = implode('', array_map('chr', $item['value']));
                    $tlvData['relays'] = $relays;
                }
                if ($item['type'] === $typeAuthor) {
                    $str = '';
                    foreach ($item['value'] as $byte) {
                        $str .= str_pad(dechex($byte), 2, '0', STR_PAD_LEFT);
                    }
                    $author = $str;
                    $tlvData['author'] = $author;
                }
                if ($item['type'] === $typeKind) {
                    // big-endian integer
                    $intValue = 0;
                    foreach ($item['value'] as $byte) {
                        $intValue = ($intValue << 8) | $byte;
                    }
                    $kind = (string) $intValue;
                    $tlvData['kind'] = $kind;
                }
            }
            return $tlvData;
        } catch (Bech32Exception $e) {
            throw new \RuntimeException($e->getMessage());
        }
    }

    /**
     * Encode hex formatted string, Event or Profile to a bech32 formatted string.
     *
     * @param string|Event|Profile $data
     * @param string $prefix
     * @param array $metadata
     * @return string
     * @throws Bech32Exception
     */
    public function encode(string|Event|Profile $data, string $prefix, array $metadata = []): string
    {
        if ($data instanceof Event) {
            $event = $data;
            /*
             * TODO create TLV / metadata class for this structure so we can it as an object
             * TODO validate metadata array here
             * only allowed keys are:
             * - id (hex event id)
             * - dTag (unique identifier string)
             * - author (hex pubkey)
             * - relays (array)
             * - kind (integer)
             */
            try {
                switch ($prefix) {
                    case 'nevent':
                        $bytes_array = $this->convertEventToBytes($event, $metadata);
                        break;
                    case 'naddr':
                        $bytes_array = $this->convertAddressableEventToBytes($event, $metadata);
                        break;
                    case 'nprofile':
                        $bytes_array = $this->convertProfileToBytes($event, $metadata);
                        break;
                }
                $checksum = new Checksum($prefix, Bits::encode($bytes_array));
                $bech32_string = $checksum(
                    fn(string $encoded, int $character) => $encoded .= Bech32::BECH32_CHARSET[$character]
                );
                return $bech32_string;
            } catch (\Exception $e) {
                throw new \RuntimeException($e->getMessage());
            }
        } else {
            return $this->convertToBech32($data, $prefix);
        }
    }

    /**
     * @throws Bech32Exception
     */
    public function encodeNote(string $event_hex): string
    {
        return $this->convertToBech32($event_hex, 'note');
    }

    /**
     * Convert a hex formatted event to a bech32 encoded `nevent` value with metadata (TLV).
     *
     * @param Event $event
     * @param array $relays
     * @param string $author
     * @param int|null $kind
     * @return string
     * @throws \Exception
     */
    public function encodeEvent(Event $event, array $relays = [], string $author = '', int $kind = null): string
    {
        $prefix = 'nevent';
        // TODO convert this array with this structure to a TLV class
        $metadata = [
            'id' => $event->getId(),
            // TODO how do we check if there are some relays set on the event?
            // iterate over the tags field and look for r-tags with values
            'relays' => $relays,
            'author' => $author !== '' ? $author : $event->getPublicKey(),
            'kind' => $kind ?? $event->getKind()
        ];
        $bytes_array = $this->convertEventToBytes($event, $metadata);
        $checksum = new Checksum($prefix, Bits::encode($bytes_array));
        $bech32_string = $checksum(
            fn(string $encoded, int $character) => $encoded .= Bech32::BECH32_CHARSET[$character]
        );
        return $bech32_string;
    }

    /**
     * Convert a hex formatted event to a bech32 encoded `naddr` value with metadata (TLV).
     *
     * @param Event $event
     * @param string $dTag
     * @param int $kind
     * @param string $author
     * @param array $relays
     * @return string
     * @throws \Exception
     */
    public function encodeAddr(Event $event, string $dTag, int $kind, string $author = '', array $relays = []): string
    {
        $prefix = 'naddr';
        $metadata = [
            'dTag' => $dTag,
            'relays' => $relays,
            'author' => $author !== '' ? $author : $event->getPublicKey(),
            'kind' => $kind,
        ];
        $bytes_array = $this->convertAddressableEventToBytes($event, $metadata);
        $checksum = new Checksum($prefix, Bits::encode($bytes_array));
        $bech32_string = $checksum(
            fn(string $encoded, int $character) => $encoded .= Bech32::BECH32_CHARSET[$character]
        );
        return $bech32_string;
    }

    /**
     * @param Profile $profile
     * @param array $relays
     * @return string
     * @throws \Exception
     */
    public function encodeProfile(Profile $profile, array $relays = []): string
    {
        $prefix = 'nprofile';
        $metadata = [
            'relays' => $relays,
        ];
        $bytes_array = $this->convertProfileToBytes($profile, $metadata);
        $checksum = new Checksum($prefix, Bits::encode($bytes_array));
        $bech32_string = $checksum(
            fn(string $encoded, int $character) => $encoded .= Bech32::BECH32_CHARSET[$character]
        );
        return $bech32_string;
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

    /**
     * @param string $key
     * @param string $prefix
     * @return string
     * @throws Bech32Exception
     */
    private function convertToBech32(string $key, string $prefix): string
    {
        $str = '';

        /** @var array $dec */
        // This is our bits array with decimal formatted values.
        $dec = [];
        /** @var array $split */
        // Split string into data chucks with a max length of 2 chars each chunk. This will create the byte array.
        $split = str_split($key, 2);
        foreach ($split as $item) {
            // Loop over the byte array and convert each chuck from a hex formatted value into a decimal formatted chunks.
            $dec[] = hexdec($item);
        }
        // Convert the bits array to a bytes array.
        $bytes = convertBits($dec, count($dec), 8, 5);
        $str = encode($prefix, $bytes);

        return $str;
    }

    /**
     * Convert event to bytes with metadata.
     *
     * @param Event $event
     * @param array $metadata
     * @return array
     */
    private function convertEventToBytes(Event $event, array $metadata) : array
    {
        $id = [
            Bech32::fromHexToBytes($event->getId())
        ];
        $relays = Bech32::fromRelaysToBytes(
            $metadata['relays'] ?? []
        );
        $pubkey = isset($metadata['author']) ?
            [Bech32::fromHexToBytes($metadata['author'])] :
            [Bech32::fromHexToBytes($event->getPublicKey())];
        $kind = [
            Bech32::fromIntegerToBytes($event->getKind())
        ];
        return Bech32::encodeTLV($id, $relays, $pubkey, $kind);
    }

    private function convertAddressableEventToBytes(Event $event, array $metadata): array
    {
        $identifier = [
            Bech32::fromUTF8ToBytes($metadata['dTag'])
        ];
        $relays = Bech32::fromRelaysToBytes(
            $metadata['relays'] ?? []
        );
        $pubkey = isset($metadata['author']) ?
            [Bech32::fromHexToBytes($metadata['author'])] :
            [Bech32::fromHexToBytes($event->getPublicKey())];
        $kind = [
            Bech32::fromIntegerToBytes($event->getKind())
        ];
        return Bech32::encodeTLV($identifier, $relays, $pubkey, $kind);
    }

    private function convertProfileToBytes(Profile $profile, array $metadata) : array
    {
        $pubkey = isset($metadata['author']) ?
            [Bech32::fromHexToBytes($metadata['author'])] :
            [Bech32::fromHexToBytes($profile->getPublicKey())];
        $relays = Bech32::fromRelaysToBytes(
            $metadata['relays'] ?? []
        );
        return Bech32::encodeTLV($pubkey, $relays);
    }
}
