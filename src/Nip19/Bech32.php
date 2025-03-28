<?php

declare(strict_types=1);

namespace swentel\nostr\Nip19;

use swentel\nostr\Nip19\Identifiers\NEvent;

/**
 * https://github.com/nostriphant/nip-19/blob/main/src/Bech32.php
 */
class Bech32
{

    public IdentifierInterface $data;

    const TYPE_MAP = [
//        'nsec' => Data\NSec::class,
//        'npub' => Data\NPub::class,
//        'note' => Data\Note::class,
//        'nprofile' => Data\NProfile::class,
//        'naddr' => Data\NAddr::class,
//        'ncryptsec' => Data\NCryptSec::class,
        'nevent' => NEvent::class
    ];
    const BECH32_MAX_LENGTH = 5000;
    const BECH32_CHARSET = 'qpzry9x8gf2tvdw0s3jn54khce6mua7l';
    const CHARKEY_KEY = [
        -1,
        -1,
        -1,
        -1,
        -1,
        -1,
        -1,
        -1,
        -1,
        -1,
        -1,
        -1,
        -1,
        -1,
        -1,
        -1,
        -1,
        -1,
        -1,
        -1,
        -1,
        -1,
        -1,
        -1,
        -1,
        -1,
        -1,
        -1,
        -1,
        -1,
        -1,
        -1,
        -1,
        -1,
        -1,
        -1,
        -1,
        -1,
        -1,
        -1,
        -1,
        -1,
        -1,
        -1,
        -1,
        -1,
        -1,
        -1,
        15,
        -1,
        10,
        17,
        21,
        20,
        26,
        30,
        7,
        5,
        -1,
        -1,
        -1,
        -1,
        -1,
        -1,
        -1,
        29,
        -1,
        24,
        13,
        25,
        9,
        8,
        23,
        -1,
        18,
        22,
        31,
        27,
        19,
        -1,
        1,
        0,
        3,
        16,
        11,
        28,
        12,
        14,
        6,
        4,
        2,
        -1,
        -1,
        -1,
        -1,
        -1,
        -1,
        29,
        -1,
        24,
        13,
        25,
        9,
        8,
        23,
        -1,
        18,
        22,
        31,
        27,
        19,
        -1,
        1,
        0,
        3,
        16,
        11,
        28,
        12,
        14,
        6,
        4,
        2,
        -1,
        -1,
        -1,
        -1,
        -1
    ];

    public function __construct(private string $bech32)
    {
        $length = strlen($bech32);

        if ($length < 8 || $length > self::BECH32_MAX_LENGTH) {
            throw new \Exception(
                "invalid string length: $length ($bech32). Expected (8.." . self::BECH32_MAX_LENGTH . ")"
            );
        }

        $chars = array_values(unpack('C*', $bech32));

        $haveUpper = false;
        $haveLower = false;
        $positionOne = -1;

        for ($i = 0; $i < $length; $i++) {
            $x = $chars[$i];
            if ($x < 33 || $x > 126) {
                throw new \Exception('Out of range character in bech32 string');
            }

            if ($x >= 0x61 && $x <= 0x7a) {
                $haveLower = true;
            }

            if ($x >= 0x41 && $x <= 0x5a) {
                $haveUpper = true;
                $x = $chars[$i] = $x + 0x20;
            }

            if ($x === 0x31) {
                $positionOne = $i;
            }
        }

        if ($haveUpper && $haveLower) {
            throw new \Exception('Data contains mixture of higher/lower case characters');
        }

        if ($positionOne === -1) {
            throw new \Exception("Missing separator character");
        }

        if ($positionOne < 1) {
            throw new \Exception("Empty HRP");
        }

        if (($positionOne + 7) > $length) {
            throw new \Exception('Too short checksum');
        }

        $hrp = \pack("C*", ...\array_slice($chars, 0, $positionOne));

        $data = array_values(
            array_map(
                fn($char) => ($char & 0x80) ? -1 : self::CHARKEY_KEY[$char],
                array_slice($chars, $positionOne + 1)
            )
        );

        $stripped = Checksum::validate($hrp, $data);
        if ($stripped === false) {
            throw new \Exception('Invalid bech32 checksum');
        }
        $this->data = new (self::TYPE_MAP[$hrp])(Bits::decode($stripped));
    }

    public function __get(string $name): mixed
    {
        if ($name === 'type') {
            $class = get_class($this->data);
            return strtolower(substr($class, strrpos($class, '\\') + 1));
        }
    }

    static function __callStatic(string $name, array $arguments): self
    {
        $bytes = call_user_func_array([self::TYPE_MAP[$name], 'toBytes'], $arguments);
        $checksum = new Checksum($name, Bits::encode($bytes));
        return new self($checksum(fn(string $encoded, int $character) => $encoded .= self::BECH32_CHARSET[$character]));
    }

    public function __toString(): string
    {
        return $this->bech32;
    }

    public function __invoke(): mixed
    {
        return ($this->data)();
    }

    static function array_entries(array $array)
    {
        return array_map(fn(mixed $key, mixed $value) => [$key, $value], array_keys($array), array_values($array));
    }

    static function parseTLVRelays(array $tlv): array
    {
        return isset($tlv[1]) ? array_map([self::class, 'fromBytesToUTF8'], $tlv[1]) : [];
    }

    static function parseTLVKind(array $tlv): ?int
    {
        return isset($tlv[3][0]) ? self::fromBytesToInteger($tlv[3][0]) : null;
    }

    static function parseTLVAuthor(array $tlv): ?string
    {
        return isset($tlv[2][0]) ? self::fromBytesToHex($tlv[2][0]) : null;
    }

    static function parseTLV(array $bytes): array
    {
        $result = [];
        $rest = $bytes;
        while (count($rest) > 0) {
            $type = array_shift($rest);
            $length = array_shift($rest);
            $value = array_slice($rest, 0, $length);
            if (count($value) < $length) {
                throw new \Exception('not enough data to read on TLV ' . $type);
            }
            $rest = array_slice($rest, $length);
            $result[$type] = $result[$type] ?? [];
            $result[$type][] = $value;
        }
        return $result;
    }

    static function encodeTLV(array ...$tlv): array
    {
        return array_reduce(self::array_entries($tlv), function (array $carry, array $tlv_entry): array {
            return array_reduce($tlv_entry[1], function (array $carry, array $value) use ($tlv_entry): array {
                return array_merge($carry, [$tlv_entry[0], count($value)], $value);
            }, $carry);
        }, []);
    }

    static function fromBytesToHex(array $bytes): string
    {
        return array_reduce(
            $bytes,
            fn(string $hex, int $item) => $hex .= str_pad(dechex($item), 2, '0', STR_PAD_LEFT),
            ''
        );
    }

    static function fromBytesToInteger(array $bytes): int
    {
        return hexdec(self::fromBytesToHex($bytes));
    }

    static function fromBytesToUTF8(array $bytes): string
    {
        return array_reduce($bytes, fn(string $utf8, int $item) => $utf8 .= chr($item), '');
    }

    static function fromHexToBytes(#[\SensitiveParameter] string $hex_key): array
    {
        return array_map('hexdec', str_split($hex_key, 2));
    }

    static function fromUTF8ToBytes(string $utf8): array
    {
        return array_map('ord', mb_str_split($utf8));
    }

    static function fromRelaysToBytes(array $relays): array
    {
        return array_map([self::class, 'fromUTF8ToBytes'], $relays);
    }

    static function fromIntegerToBytes(int $integer): array
    {
        // Create a Uint8Array with enough space to hold a 32-bit integer (4 bytes).
        $uint8Array = [];

        // Use bitwise operations to extract the bytes.
        $uint8Array[0] = ($integer >> 24) & 0xff; // Most significant byte (MSB)
        $uint8Array[1] = ($integer >> 16) & 0xff;
        $uint8Array[2] = ($integer >> 8) & 0xff;
        $uint8Array[3] = $integer & 0xff; // Least significant byte (LSB)

        return $uint8Array;
    }

    static function isValid(string $expected_type, string $bech32)
    {
        try {
            $decoded = new self($bech32);
            return $decoded->type === $expected_type;
        } catch (\Exception $ex) {
        }
        return false;
    }

    static function isValidNProfile(string $bech32): bool
    {
        return self::isValid('nprofile', $bech32);
    }

    static function isValidNAddress(string $bech32): bool
    {
        return self::isValid('naddr', $bech32);
    }

    static function isValidNSec(string $bech32): bool
    {
        return self::isValid('nsec', $bech32);
    }

    static function isValidNPub(string $bech32): bool
    {
        return self::isValid('npub', $bech32);
    }

    static function isValidNote(string $bech32): bool
    {
        return self::isValid('note', $bech32);
    }

    static function isValidNCryptSec(string $bech32): bool
    {
        return self::isValid('ncryptsec', $bech32);
    }

    static function isValidNEvent(string $bech32): bool
    {
        return self::isValid('nevent', $bech32);
    }
}
