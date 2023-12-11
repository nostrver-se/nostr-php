<?php

declare(strict_types=1);

namespace swentel\nostr\Nip19;

/**
 * Nip19 bech32-encoded entities
 * https://github.com/nbd-wtf/go-nostr/blob/master/nip19/nip19.go
 * https://github.com/Bit-Wasp/bech32/blob/master/src/bech32.php
 */
class Nip19
{
    /**
     * @var string $prefix
     */
    protected $prefix;

    public function __construct() {}

    public function decode(string $bech32string)
    {
        $length = strlen($bech32string);
        if ($length > 90) {
            throw new \Exception('Bech32 string cannot exceed 90 characters in length');
        }
        if ($length < 8) {
            throw new \Exception('Bech32 string is too short');
        }
    }

    /**
     * @throws \Exception
     */
    public function encode(string $value)
    {
        $prefix = '';
        switch($prefix) {
            case 'npub':
                break;
            case 'nsec':
                break;
            case 'note':
                break;
            default:
                throw new \Exception('Unexpected value');
        }
    }

}
