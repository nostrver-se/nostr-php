<?php

declare(strict_types=1);

namespace swentel\nostr\Nip19;

/**
 * Bech32 class which originally is copy-pasted from
 * https://github.com/nostriphant/nip-19/blob/main/src/Bech32.php
 *
 */
class Bech32
{
    public const BECH32_MAX_LENGTH = 5000;
    public const BECH32_CHARSET = 'qpzry9x8gf2tvdw0s3jn54khce6mua7l';
    public const CHARKEY_KEY = [
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
        -1,
    ];
    private $data;

    public function __construct(private string $bech32)
    {
        $length = strlen($bech32);

        if ($length < 8 || $length > self::BECH32_MAX_LENGTH) {
            throw new \RuntimeException(
                "invalid string length: $length ($bech32). Expected (8.." . self::BECH32_MAX_LENGTH . ")",
            );
        }

        $chars = array_values(unpack('C*', $bech32));

        $haveUpper = false;
        $haveLower = false;
        $positionOne = -1;

        for ($i = 0; $i < $length; $i++) {
            $x = $chars[$i];
            if ($x < 33 || $x > 126) {
                throw new \RuntimeException('Out of range character in bech32 string');
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
            throw new \RuntimeException('Data contains mixture of higher/lower case characters');
        }

        if ($positionOne === -1) {
            throw new \RuntimeException("Missing separator character");
        }

        if ($positionOne < 1) {
            throw new \RuntimeException("Empty HRP");
        }

        if (($positionOne + 7) > $length) {
            throw new \RuntimeException('Too short checksum');
        }

        $hrp = \pack("C*", ...\array_slice($chars, 0, $positionOne));

        $data = array_values(
            array_map(
                fn($char) => ($char & 0x80) ? -1 : self::CHARKEY_KEY[$char],
                array_slice($chars, $positionOne + 1),
            ),
        );

        $stripped = Checksum::validate($hrp, $data);
        if ($stripped === false) {
            throw new \RuntimeException('Invalid bech32 checksum');
        }
    }

    public function __toString(): string
    {
        return $this->bech32;
    }

    public function __invoke(): mixed
    {
        return ($this->data)();
    }

    /**
     * @param array $array
     * @return array
     */
    public static function arrayEntries(array $array): array
    {
        return array_map(fn(mixed $key, mixed $value) => [$key, $value], array_keys($array), array_values($array));
    }

    public static function parseTLVRelays(array $tlv): array
    {
        return isset($tlv[1]) ? array_map([self::class, 'fromBytesToUTF8'], $tlv[1]) : [];
    }

    public static function parseTLVKind(array $tlv): ?int
    {
        return isset($tlv[3][0]) ? self::fromBytesToInteger($tlv[3][0]) : null;
    }

    public static function parseTLVAuthor(array $tlv): ?string
    {
        return isset($tlv[2][0]) ? self::fromBytesToHex($tlv[2][0]) : null;
    }

    public static function parseTLV(array $bytes): array
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

    public static function encodeTLV(array ...$tlv): array
    {
        return array_reduce(self::arrayEntries($tlv), function (array $carry, array $tlv_entry): array {
            return array_reduce($tlv_entry[1], function (array $carry, array $value) use ($tlv_entry): array {
                return array_merge($carry, [$tlv_entry[0], count($value)], $value);
            }, $carry);
        }, []);
    }

    /**
     *  Format array with bytes to hex formatted string.
     *
     * @param array $bytes
     * @return string
     */
    public static function fromBytesToHex(array $bytes): string
    {
        return array_reduce(
            $bytes,
            fn(string $hex, int $item) => $hex .= str_pad(dechex($item), 2, '0', STR_PAD_LEFT),
            '',
        );
    }

    /**
     * Format array with bytes to int.
     *
     * @param array $bytes
     * @return int
     */
    public static function fromBytesToInteger(array $bytes): int
    {
        return hexdec(self::fromBytesToHex($bytes));
    }

    /**
     * Format bytes array to UTF8 formatted string.
     *
     * @param array $bytes
     * @return string
     */
    public static function fromBytesToUTF8(array $bytes): string
    {
        return array_reduce($bytes, fn(string $utf8, int $item) => $utf8 .= chr($item), '');
    }

    /**
     * Format hex formatted string to array with bytes.
     *
     * @param string $hex_key
     * @return array
     */
    public static function fromHexToBytes(#[\SensitiveParameter] string $hex_key): array
    {
        return array_map('hexdec', str_split($hex_key, 2));
    }

    /**
     * Format UTF8 formatted string to array with bytes.
     *
     * @param string $utf8
     * @return array
     */
    public static function fromUTF8ToBytes(string $utf8): array
    {
        return array_map('ord', mb_str_split($utf8));
    }

    /**
     * Format array with relays to array with bytes.
     *
     * @param array $relays
     * @return array
     */
    public static function fromRelaysToBytes(array $relays): array
    {
        return array_map([self::class, 'fromUTF8ToBytes'], $relays);
    }

    /**
     * Format int value to array with bytes.
     *
     * @param int $integer
     * @return array
     */
    public static function fromIntegerToBytes(int $integer): array
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
}
