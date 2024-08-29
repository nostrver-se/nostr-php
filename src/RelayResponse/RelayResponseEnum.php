<?php

declare(strict_types=1);

namespace swentel\nostr\RelayResponse;

/**
 * Enum with response types.
 */
enum RelayResponseEnum: string
{
    case EVENT = 'EVENT';
    case OK = 'OK';
    case EOSE = 'EOSE';
    case CLOSED = 'CLOSED';
    case NOTICE = 'NOTICE';
    /**
     * NIP-42 support - Authentication of clients to relays
     * https://github.com/nostr-protocol/nips/blob/master/42.md
     */
    case AUTH = 'AUTH';
}
