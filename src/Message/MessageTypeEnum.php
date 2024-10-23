<?php

declare(strict_types=1);

namespace swentel\nostr\Message;

/**
 * Enum with message types.
 */
enum MessageTypeEnum: string
{
    case EVENT = 'EVENT';
    case REQUEST = 'REQ';
    case CLOSE = 'CLOSE';
    case AUTH = 'AUTH';
}
