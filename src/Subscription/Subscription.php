<?php

declare(strict_types=1);

namespace swentel\nostr\Subscription;

use swentel\nostr\SubscriptionInterface;

class Subscription implements SubscriptionInterface
{
    public function setId($length = 64): string
    {
        // String of all alphanumeric character
        $str_result = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';
        // Shuffle the $str_result and returns substring of specified length
        return substr(str_shuffle($str_result), 0, $length);
    }
}
