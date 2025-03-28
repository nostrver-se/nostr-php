<?php

declare(strict_types=1);

namespace swentel\nostr\Nip19;

/**
 * https://nips.nostr.com/19#shareable-identifiers-with-extra-metadata
 *
 * https://github.com/nostriphant/nip-19/blob/main/src/Data.php
 */
interface IdentifierInterface
{
    public function __construct(array $bytes);

    public function __invoke();

    /**
     * @param mixed ...$data
     * @return mixed
     */
    public static function toBytes(mixed ...$data);
}
