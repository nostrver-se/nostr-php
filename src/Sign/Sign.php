<?php

declare(strict_types=1);

namespace swentel\nostr\Sign;

use Mdanter\Ecc\Crypto\Signature\SchnorrSignature;
use swentel\nostr\EventInterface;
use swentel\nostr\Key\Key;

class Sign
{
    /**
     * Sign an event.
     *
     * @param EventInterface $event
     *   The event to be signed.
     * @param string $private_key
     *   The private key.
     */
    public function signEvent(EventInterface $event, string $private_key): void
    {
        $key = new Key();
        // Convert to hex if private key is bech32 formatted.
        if (str_starts_with($private_key, 'nsec') === true) {
            $private_key = $key->convertToHex($private_key);
        }
        $event->setPublicKey($key->getPublicKey($private_key));

        $hash_content = $this->serializeEvent($event);
        if ($hash_content) {

            if ($event->getId() === '') {
                $id = hash('sha256', $hash_content);
                $event->setId($id);
            }

            $sign = new SchnorrSignature();
            $signature = $sign->sign($private_key, $event->getId());
            $event->setSignature($signature['signature']);
        }
    }

    /**
     * Serialize the event so the id can be created.
     *
     * @param EventInterface $event
     *
     * @return bool|string
     */
    public static function serializeEvent(EventInterface $event): bool|string
    {
        $array =
        [
            0,
            $event->getPublicKey(),
            $event->getCreatedAt(),
            $event->getKind(),
            $event->getTags(),
            $event->getContent(),
        ];
        return json_encode($array, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }
}
