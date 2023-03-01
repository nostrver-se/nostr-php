<?php

namespace swentel\nostr;

use Mdanter\Ecc\Crypto\Signature\SchnorrSignature;

class Sign
{

    /**
     * Sign an event.
     *
     * @param array $event
     * @param string $private_key
     *
     * @return array
     */
    public function sign(array $event, string $private_key): array
    {
        $hash_content = $this->generateHash($event);
        if ($hash_content)
        {
            $id = hash('sha256', utf8_encode($hash_content));
            $event['id'] = $id;

            $sign = new SchnorrSignature();
            $signature = $sign->sign($private_key, $event['id']);
            $event['sig'] = $signature['signature'];
        }

        return $event;
    }

    /**
     * Generate the hash from an array suitable for nostr.
     *
     * @param array $array
     *
     * @return string
     */
    public function generateHash(array $array): string
    {
        // TODO, I have no idea why this fails, investigate.
        /*$merged = array_merge([0], array_values($array));
        return json_encode($merged);*/
        $hash_content = '[0';
        foreach ($array as $val)
        {
            if (is_numeric($val)) {
                $hash_content .= ',' . $val;
            }
            elseif (is_array($val)) {
                // TODO these are tags. hardcoded for now.
                $hash_content .= ',[]';
            }
            else
            {
                $hash_content .= ',"' . $val . '"';
            }
        }
        $hash_content .= ']';
        return $hash_content;
    }

}
