<?php

declare(strict_types=1);

namespace swentel\nostr\Nip19;

/**
 * https://github.com/nostriphant/nip-19/blob/main/src/Bits.php
 */
class Bits
{
    /**
     * @param array $data
     * @return array
     * @throws \Exception
     */
    public static function encode(array $data): array
    {
        return self::convert($data, 8, 5, true);
    }

    /**
     * @param array $data
     * @return array
     * @throws \Exception
     */
    public static function decode(array $data): array
    {
        return self::convert($data, 5, 8, false);
    }

    /**
     * @param array $data
     * @param int $fromBits
     * @param int $toBits
     * @param bool $pad
     * @return array
     * @throws \Exception
     */
    public static function convert(array $data, int $fromBits, int $toBits, bool $pad = true): array
    {
        $inLen = count($data);
        $acc = 0;
        $bits = 0;
        $ret = [];
        $maxv = (1 << $toBits) - 1;
        $maxacc = (1 << ($fromBits + $toBits - 1)) - 1;

        for ($i = 0; $i < $inLen; $i++) {
            $value = $data[$i];
            if ($value < 0 || $value >> $fromBits) {
                throw new \Exception('Invalid value for convert bits');
            }

            $acc = (($acc << $fromBits) | $value) & $maxacc;
            $bits += $fromBits;

            while ($bits >= $toBits) {
                $bits -= $toBits;
                $ret[] = (($acc >> $bits) & $maxv);
            }
        }

        if ($pad) {
            if ($bits) {
                $ret[] = ($acc << $toBits - $bits) & $maxv;
            }
        } elseif ($bits >= $fromBits || ((($acc << ($toBits - $bits))) & $maxv)) {
            throw new \Exception('Invalid data');
        }

        return $ret;
    }
}
