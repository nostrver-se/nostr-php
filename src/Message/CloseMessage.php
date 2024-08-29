<?php

declare(strict_types=1);

namespace swentel\nostr\Message;

use swentel\nostr\MessageInterface;

class CloseMessage implements MessageInterface
{
    /**
     * @var string $type
     */
    private string $type;

    /**
     * Subscription ID
     */
    protected string $subscriptionId;

    public function __construct(string $subscriptionId)
    {
        $this->subscriptionId = $subscriptionId;
        $this->setType(MessageTypeEnum::CLOSE);
    }

    /**
     * Set message type.
     *
     * @param MessageTypeEnum $type
     * @return void
     */
    public function setType(MessageTypeEnum $type): void
    {
        $this->type = $type->value;
    }

    /**
     * {@inheritdoc}
     */
    public function generate(): string
    {
        return '["' . $this->type . '", "' . $this->subscriptionId . '"]';
    }
}
