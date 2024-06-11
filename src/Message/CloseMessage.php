<?php

declare(strict_types=1);

namespace swentel\nostr\Message;

use swentel\nostr\MessageInterface;

class CloseMessage implements MessageInterface
{
    /**
     * Subscription ID
     */
    protected string $subscriptionId;

    public function __construct(string $subscriptionId)
    {
        $this->subscriptionId = $subscriptionId;
    }

    /**
     * {@inheritdoc}
     */
    public function generate(): string
    {
        return '["CLOSE", "' . $this->subscriptionId . '"]';
    }
}
