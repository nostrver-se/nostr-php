<?php

declare(strict_types=1);

namespace swentel\nostr\Nip19\Identifiers;

use swentel\nostr\NIP19\IdentifierInterface;
use swentel\nostr\NIP19\Bech32;

class NAddr implements IdentifierInterface
{

    public string $identifier;
    public string $pubkey;
    public int $kind;
    public array $relays;

    #[\Override]
    public function __construct(array $bytes)
    {
        try {
            $tlv = Bech32::parseTLV($bytes);
        } catch (\Exception $e) {
            throw new \RuntimeException($e->getMessage());
        }
        $this->identifier = Bech32::fromBytesToUTF8($tlv[0][0]);
        $this->pubkey = Bech32::parseTLVAuthor($tlv);
        $this->kind = Bech32::parseTLVKind($tlv);
        $this->relays = Bech32::parseTLVRelays($tlv);
    }

    #[\Override]
    public function __invoke()
    {
        return $this;
    }

    #[\Override]
    public static function toBytes(mixed ...$data): array
    {
        return Bech32::encodeTLV(
            [Bech32::fromUTF8ToBytes($data['dTag'])],
            Bech32::fromRelaysToBytes($data['relays'] ?? []),
            [Bech32::fromHexToBytes($data['pubkey'])],
            [Bech32::fromIntegerToBytes($data['kind'])],
        );
    }
}
