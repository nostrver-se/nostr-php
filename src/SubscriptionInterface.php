<?php

declare(strict_types=1);

namespace swentel\nostr;

interface SubscriptionInterface
{
    public function setId($length): string;
}
