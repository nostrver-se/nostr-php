<?php

declare(strict_types=1);

namespace swentel\nostr\Message;

use swentel\nostr\EventInterface;
use swentel\nostr\MessageInterface;

class AuthMessage implements MessageInterface
{
    /**
     * @var string $type
     */
    private string $type;

    /**
     * The event.
     *
     * @var EventInterface
     */
    protected EventInterface $event;

    public function __construct(EventInterface $event)
    {
        $this->event = $event;
        $this->setType(MessageTypeEnum::AUTH);
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
        $event = json_encode($this->event->toArray(), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        return '["' . $this->type . '", ' . $event . ']';
    }
}
