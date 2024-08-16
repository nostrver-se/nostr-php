<?php

declare(strict_types=1);

namespace swentel\nostr\RelayResponse;

/**
 * Enum with response types.
 */
enum RelayResponseEnum: string
{
    case ERROR = 'ERROR';
    case EVENT = 'EVENT';
    case OK = 'OK';
    case EOSE = 'EOSE';
    case CLOSED = 'CLOSED';
    case NOTICE = 'NOTICE';
}
