<?php

namespace swentel\nostr;

use Mdanter\Ecc\Crypto\Signature\SchnorrSignature;

class Sign
{

    public function sign(array $event, string $private_key): array
    {
        $hash_content = $this->generateHash($event);
        $id = hash('sha256', utf8_encode($hash_content));
        $event['id'] = $id;

        $sign = new SchnorrSignature();
        $signature = $sign->sign($private_key, $event['id']);
        $event['sig'] = $signature['signature'];

        return $event;
    }

    public function generateHash(array $array): bool|string
    {
        $merged = array_merge([0], $array);
        return json_encode($merged);
    }

}
