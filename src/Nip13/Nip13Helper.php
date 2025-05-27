<?php

declare(strict_types=1);

namespace swentel\nostr\Nip13;

use swentel\nostr\Event\Event;
use swentel\nostr\Sign\Sign;

/**
 * Helper class for NIP-13 (Proof of Work)
 *
 * @see https://github.com/nostr-protocol/nips/blob/master/13.md
 */
class Nip13Helper
{
    /**
     * Get the number of leading zero bits in the SHA-256 hash of the event id.
     *
     * @param string $id Hex-encoded event id
     * @return int Number of leading zero bits
     */
    public static function getPowDifficulty(string $id): int
    {
        if (!preg_match('/^[0-9a-f]+$/i', $id)) {
            throw new \InvalidArgumentException('Invalid hex string for event id');
        }

        $count = 0;
        // Process each character in the hex string
        for ($i = 0, $iMax = strlen($id); $i < $iMax; $i++) {
            $nibble = hexdec($id[$i]);

            if ($nibble === 0) {
                // Each '0' hex character represents 4 zero bits
                $count += 4;
            } else {
                // Count remaining leading zeroes in this nibble
                // This is similar to the JavaScript example from the NIP-13 spec
                if (($nibble & 8) === 0) {
                    $count++;
                } else {
                    break;
                }
                if (($nibble & 4) === 0) {
                    $count++;
                } else {
                    break;
                }
                if (($nibble & 2) === 0) {
                    $count++;
                } else {
                    break;
                }
                if (($nibble & 1) === 0) {
                    $count++;
                }
                break;
            }
        }

        return $count;
    }

    /**
     * Mine a POW nonce for an event to reach target difficulty.
     *
     * @param Event $event The event object as array without id, sig, or pubkey (unsigned)
     * @param int $targetDifficulty The target difficulty (number of leading zero bits)
     * @param string $nonceField The field to use for the nonce (default: 'nonce')
     * @return Event The event with a valid nonce added that meets the target difficulty
     */
    public static function minePow(Event $event, int $targetDifficulty, string $nonceField = 'nonce'): Event
    {
        $sig = $event->getSignature();
        if ($sig) {
            throw new \RuntimeException('Event is already signed');
        }

        // Start with a random nonce
        $nonce = bin2hex(random_bytes(4));
        $found = false;
        $tries = 0;
        $maxTries = 10000000; // Prevent infinite loops

        // Try increasing nonce values until we hit our target difficulty
        while (!$found && $tries < $maxTries) {

            $event->setTag($nonceField, [$nonce, (string) $targetDifficulty]);

            // Calculate event ID
            $eventId = hash('sha256', Sign::serializeEvent($event));

            // Check if difficulty is reached
            $difficulty = self::getPowDifficulty($eventId);
            if ($difficulty >= $targetDifficulty) {
                $found = true;
                $event->setId($eventId);
            } else {
                // Increment nonce and try again
                $nonce = bin2hex(random_bytes(4)); // Use random values for better distribution
                $tries++;
            }
        }

        if (!$found) {
            throw new \RuntimeException("Failed to find a nonce with target difficulty {$targetDifficulty} after {$maxTries} tries");
        }

        return $event;
    }
}
