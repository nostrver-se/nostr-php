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
 * PHP Bech32 package.
 * https://github.com/Bit-Wasp/bech32/blob/master/src/bech32.php
 *
 * Some of the methods are copy-pasted from
 * https://github.com/nostriphant/nip-19
 * and have been modified to match these library needs.
 *
 */
class Nip19Helper
{
    /**
     * Decode a bech32 identifier into TLV / metadata.
     * TLVs that are not recognized or supported should be ignored, rather than causing an error.
     *
     * @param string $bech32string
     * @return array
     * @throws \Exception
     */
    public function decode(string $bech32string): array
    {
        try {
            $length = strlen($bech32string);

            if ($length > Bech32::BECH32_MAX_LENGTH) {
                throw new \RuntimeException(
                    message: sprintf(
                        'Bech32 string cannot exceed %d characters in length',
                        Bech32::BECH32_MAX_LENGTH,
                    ),
                );
            }
            if ($length < 8) {
                throw new \RuntimeException(message: 'Bech32 string is too short');
            }
            // Find the separator (1)
            $pos = strrpos($bech32string, '1');
            if ($pos === false) {
                throw new \RuntimeException(message: 'Invalid bech32 string');
            }
            // Extract human-readable part (HRP)
            $prefix = substr($bech32string, 0, $pos);

            // Extract data part
            $data_part = substr($bech32string, $pos + 1);
            $data = [];
            for ($i = 0, $iMax = strlen($data_part); $i < $iMax; $i++) {
                $data[] = strpos(Bech32::BECH32_CHARSET, $data_part[$i]);
                if ($data[$i] === false) {
                    throw new \RuntimeException('Invalid character in Bech32 string');
                }
            }
            // Convert 5-bit data to 8-bit data
            $binaryData = convertBits($data, count($data), 5, 8);
            switch ($prefix) {
                case 'note':
                    // Format binary to hex with a max of 32 bytes to be processed.
                    $val = '';
                    for ($i = 0, $max = 32; $i < $max; $i++) {
                        $val .= str_pad(dechex($binaryData[$i]), 2, '0', STR_PAD_LEFT);
                    }
                    return ['event_id' => $val];
                case 'npub':
                case 'nsec':
                    return [$prefix, $binaryData];
            }
            // Parse the binary data into TLV format
            $tlvEntries = [];
            $offset = 0;
            while ($offset < count($binaryData)) {
                if ($offset + 1 > count($binaryData)) {
                    throw new \RuntimeException("Incomplete TLV data");
                }
                // Read the Type (T) and Length (L)
                $type = $binaryData[$offset];
                $length = $binaryData[$offset + 1];
                $offset += 2;

                // Ensure we have enough data for the value
                if ($offset + $length > count($binaryData)) {
                    break;
                } else {
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
            $relays = [];
            foreach ($tlvEntries as $item) {
                // The 32 bytes of the event id
                if ($item['type'] === TLVEnum::Special->value && $prefix === 'nevent') {
                    $val = '';
                    foreach ($item['value'] as $byte) {
                        $val .= str_pad(dechex($byte), 2, '0', STR_PAD_LEFT);
                    }
                    $tlvData['event_id'] = $val;
                }
                // The 32 bytes of the profile public key
                if ($item['type'] === TLVEnum::Special->value && $prefix === 'nprofile') {
                    $val = '';
                    foreach ($item['value'] as $byte) {
                        $val .= str_pad(dechex($byte), 2, '0', STR_PAD_LEFT);
                    }
                    $tlvData['pubkey'] = $val;
                }
                // identifier (the d-tag) of the event
                if ($item['type'] === TLVEnum::Special->value && $prefix === 'naddr') {
                    $val = implode('', array_map('chr', $item['value']));
                    $tlvData['identifier'] = $val;
                }
                if ($item['type'] === TLVEnum::Relay->value) {
                    $relays[] = implode('', array_map('chr', $item['value']));
                    $tlvData['relays'] = $relays;
                }
                if ($item['type'] === TLVEnum::Author->value) {
                    $str = '';
                    foreach ($item['value'] as $byte) {
                        $str .= str_pad(dechex($byte), 2, '0', STR_PAD_LEFT);
                    }
                    $author = $str;
                    $tlvData['author'] = $author;
                }
                if ($item['type'] === TLVEnum::Kind->value) {
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
            $tlv = new TLV(
                $metadata['id'] ?? $data->getId(),
                $metadata['dTag'] ?? null,
                $metadata['author'] ?? $data->getPublicKey(),
                $metadata['relays'] ?? [],
                $metadata['kind'] ?? $data->getKind(),
            );
            try {
                switch ($prefix) {
                    case 'note':
                        return $this->encodeNote($data->getId());
                    case 'nevent':
                        $bytes_array = $this->convertEventToBytes($event, $tlv);
                        break;
                    case 'naddr':
                        $bytes_array = $this->convertAddressableEventToBytes($event, $tlv);
                        break;
                    case 'nprofile':
                        $bytes_array = $this->convertProfileToBytes($event, $tlv);
                        break;
                    default:
                        throw new \RuntimeException(
                            message: sprintf(
                                "Exception: unexpected prefix value for the data to be encoded, got %s of type %s",
                                $prefix,
                                gettype($prefix),
                            ),
                        );
                }
                $checksum = new Checksum($prefix, Bits::encode($bytes_array));
                $bech32_string = $checksum(
                    fn(string $encoded, int $character) => $encoded .= Bech32::BECH32_CHARSET[$character],
                );
                return $bech32_string;
            } catch (\Exception $e) {
                throw new \RuntimeException($e->getMessage());
            }
        } else {
            switch ($prefix) {
                case 'npub':
                    if (is_string($data) === false) {
                        throw new \RuntimeException(
                            message: sprintf(
                                'Exception: pubkey value should be a hex formatted string, got %s',
                                gettype($data),
                            ),
                        );
                    }
                    return $this->encodeNpub($data);
                case 'nsec':
                    if (is_string($data) === false) {
                        throw new \RuntimeException(
                            message: sprintf(
                                'Exception: secret key value should be a hex formatted string, got %s',
                                gettype($data),
                            ),
                        );
                    }
                    return $this->encodeNsec($data);
                case 'note':
                    return $this->encodeNote($data);
                default:
                    throw new \RuntimeException(
                        message: sprintf(
                            "Exception: unexpected prefix value, got %s of type %s",
                            $prefix,
                            gettype($prefix),
                        ),
                    );
            }
        }
    }

    /**
     * @throws Bech32Exception
     */
    public function encodeNote(string $event_hex): string
    {
        return $this->convertToBech32($event_hex, 'note');
    }

    public function decodeNote(string $bech32string): array
    {
        return $this->decode($bech32string);
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
        $tlv = new TLV(
            $event->getId(),
            null,
            $author !== '' ? $author : $event->getPublicKey(),
            $relays,
            $kind ?? $event->getKind(),
        );
        $bytes_array = $this->convertEventToBytes($event, $tlv);
        $checksum = new Checksum($prefix, Bits::encode($bytes_array));
        return $checksum(
            fn(string $encoded, int $character) => $encoded .= Bech32::BECH32_CHARSET[$character],
        );
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
        $metadata = new TLV(
            $event->getId(),
            $dTag,
            $author !== '' ? $author : $event->getPublicKey(),
            $relays,
            $kind ?? $event->getKind(),
        );
        $bytes_array = $this->convertAddressableEventToBytes($event, $metadata);
        $checksum = new Checksum($prefix, Bits::encode($bytes_array));
        return $checksum(
            fn(string $encoded, int $character) => $encoded .= Bech32::BECH32_CHARSET[$character],
        );
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
        $metadata = new TLV(
            $profile->getId(),
            null,
            $profile->getPublicKey(),
            $relays,
            $profile->getKind(),
        );
        $bytes_array = $this->convertProfileToBytes($profile, $metadata);
        $checksum = new Checksum($prefix, Bits::encode($bytes_array));
        return $checksum(
            fn(string $encoded, int $character) => $encoded .= Bech32::BECH32_CHARSET[$character],
        );
    }

    /**
     * @param string $pubkey
     * @return string
     */
    public function encodeNpub(string $pubkey): string
    {
        return (new Key())->convertPublicKeyToBech32($pubkey);
    }

    /**
     * @param string $seckey
     * @return string
     */
    public function encodeNsec(string $seckey): string
    {
        return (new Key())->convertPrivateKeyToBech32($seckey);
    }

    /**
     * @param string $key
     * @param string $prefix
     * @return string
     * @throws Bech32Exception
     */
    private function convertToBech32(string $key, string $prefix): string
    {
        // This is our bits array with decimal formatted values.
        $dec = [];
        // Split string into data chucks with a max length of 2 chars each chunk. This will create the byte array.
        $split = str_split($key, 2);
        foreach ($split as $item) {
            $dec[] = hexdec($item);
        }
        // Convert the bits array to a bytes array.
        $bytes = convertBits($dec, count($dec), 8, 5);
        return encode($prefix, $bytes);
    }

    /**
     * Convert replaceable event to bytes with metadata.
     *
     * @param Event $event
     * @param TLV $metadata
     * @return array
     */
    private function convertEventToBytes(Event $event, TLV $metadata): array
    {
        $id = [
            Bech32::fromHexToBytes($event->getId()),
        ];
        $relays = Bech32::fromRelaysToBytes(
            $metadata->getRelays() ?? [],
        );
        $pubkey = $metadata->getAuthor() ?
            [Bech32::fromHexToBytes($metadata->getAuthor())] :
            [Bech32::fromHexToBytes($event->getPublicKey())];
        $kind = [
            Bech32::fromIntegerToBytes($event->getKind()),
        ];
        return Bech32::encodeTLV($id, $relays, $pubkey, $kind);
    }

    /**
     *  Convert addressable event to bytes with metadata.
     *
     * @param Event $event
     * @param TLV $metadata
     * @return array
     */
    private function convertAddressableEventToBytes(Event $event, TLV $metadata): array
    {
        if (is_null($metadata->getDTag())) {
            throw new \RuntimeException(
                message: 'Exception: a dTag value is required for encoding a naddr bech32 encoded entity, got NULL',
            );
        }
        $identifier = [
            Bech32::fromUTF8ToBytes($metadata->getDTag()),
        ];
        $relays = Bech32::fromRelaysToBytes(
            $metadata->getRelays() ?? [],
        );
        $pubkey = $metadata->getAuthor() ?
            [Bech32::fromHexToBytes($metadata->getAuthor())] :
            [Bech32::fromHexToBytes($event->getPublicKey())];
        $kind = [
            Bech32::fromIntegerToBytes($event->getKind()),
        ];
        return Bech32::encodeTLV($identifier, $relays, $pubkey, $kind);
    }

    /**
     * @param Profile $profile
     * @param TLV $metadata
     * @return array
     */
    private function convertProfileToBytes(Profile $profile, TLV $metadata): array
    {
        $pubkey = $metadata->getAuthor() ?
            [Bech32::fromHexToBytes($metadata->getAuthor())] :
            [Bech32::fromHexToBytes($profile->getPublicKey())];
        $relays = Bech32::fromRelaysToBytes(
            $metadata->getRelays() ?? [],
        );
        return Bech32::encodeTLV($pubkey, $relays);
    }
}
