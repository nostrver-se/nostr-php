<?php

namespace swentel\nostr;

use BitWasp\Bech32\Exception\Bech32Exception;
use function BitWasp\Bech32\convertBits;
use function BitWasp\Bech32\decode;

class Keys
{

    public function convertKeyToHex($key): string
    {
        $str = '';
        try {
            $decoded = decode($key);
            $data = $decoded[1];
            $bytes = convertBits($data, count($data), 5, 8, FALSE);
            foreach ($bytes as $item) {
                $str .= str_pad(dechex($item), 2, '0', STR_PAD_LEFT);
            }
        }
        catch (Bech32Exception $ignored) {}

        return $str;
    }

}