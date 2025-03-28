<?php

declare(strict_types=1);

namespace swentel\nostr\Nip19\Identifiers;

use swentel\nostr\NIP19\IdentifierInterface;
use swentel\nostr\NIP19\Bech32;

/**
 * https://github.com/nostriphant/nip-19/blob/main/src/Data/NEvent.php
 */
class NEvent implements IdentifierInterface
{

    public string $id;
    public array $relays;
    public ?string $author;
    public ?int $kind;

    #[\Override]
    public function __construct(array $bytes)
    {
        try {
            $tlv = Bech32::parseTLV($bytes);
        } catch (\Exception $e) {
            throw new \RuntimeException($e->getMessage());
        }
        $this->id = Bech32::fromBytesToHex($tlv[0][0]);
        $this->relays = Bech32::parseTLVRelays($tlv);
        $this->author = Bech32::parseTLVAuthor($tlv);
        $this->kind = Bech32::parseTLVKind($tlv);
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
            [Bech32::fromHexToBytes($data['id'])],
            Bech32::fromRelaysToBytes($data['relays'] ?? []),
            isset($data['author']) ? [Bech32::fromHexToBytes($data['author'])] : [],
            [Bech32::fromIntegerToBytes($data['kind'])],
        );
    }
}
