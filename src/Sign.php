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
    public function signEvent(array $event, string $private_key): array
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
     * Generates an event message.
     *
     * @param array $event
     *   The event to encode, containing id and sign created in sign().
     *
     * @return false|string
     */
    public function generateEvent(array $event): bool|string
    {
        return '["EVENT", ' . json_encode($event, JSON_UNESCAPED_SLASHES) . ']';
    }

    /**
     * Generate the hash from an array suitable for nostr.
     *
     * @param array $array
     *
     * @return bool|string
     */
    public function generateHash(array $array): bool|string
    {
        $merged = array_merge([0], array_values($array));
        return json_encode($merged, JSON_UNESCAPED_SLASHES);
    }

}
