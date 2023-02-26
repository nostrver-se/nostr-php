<?php

namespace swentel\nostr;

use Mdanter\Ecc\Crypto\Signature\SchnorrSignature;

class Sign
{

    public function sign(array $event, string $private_key)
    {

        // This is weird, but it works. json_encode works differently on an
        // array than JSON.stringify.
        $hash_content = '[0';
        foreach ($event as $val)
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

        $id = hash('sha256', utf8_encode($hash_content));
        $event['id'] = $id;

        $sign = new SchnorrSignature();
        $signature = $sign->sign($private_key, $event['id']);
        $event['sig'] = $signature['signature'];

        return $event;
    }

}
