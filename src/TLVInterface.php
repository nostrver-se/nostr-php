<?php

declare(strict_types=1);

namespace swentel\nostr;

use swentel\nostr\Relay\RelaySet;

/**
 * https://nips.nostr.com/19#shareable-identifiers-with-extra-metadata
 */
interface TLVInterface
{
    /**
     * Set id.
     *
     * @param string $id
     * @return $this
     */
    public function setId(string $id): static;

    /**
     * Set identifier (d-tag).
     *
     * @param string $dTag
     * @return $this
     */
    public function setDTag(string $dTag): static;

    /**
     * Set author (hex formatted pybkey).
     *
     * @param string $author
     * @return $this
     */
    public function setAuthor(string $author): static;

    /**
     * Set relays.
     *
     * @param array $relays
     * @return $this
     */
    public function setRelays(array $relays): static;

    /**
     * Get relays.
     *
     * @return RelaySet|array|null
     */
    public function getRelays(): RelaySet|array|null;

    /**
     * Set kind number.
     *
     * @param int $kind
     * @return $this
     */
    public function setKind(int $kind): static;
}
