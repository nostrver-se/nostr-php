<?php

declare(strict_types=1);

namespace swentel\nostr\Nip19;

/**
 * Enum with TLV types.
 */
enum TLVEnum: int
{
    case TLVDefault = 0;
    case TLVRelay   = 1;
    case TLVAuthor  = 2;
    case TLVKind    = 3;
}