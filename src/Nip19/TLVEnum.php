<?php

declare(strict_types=1);

namespace swentel\nostr\Nip19;

/**
 * Enum with TLV types.
 */
enum TLVEnum: int
{
    case Special = 0;
    case Relay   = 1;
    case Author  = 2;
    case Kind    = 3;
}
