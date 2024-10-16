<?php

declare(strict_types=1);

namespace swentel\nostr\Message;

use swentel\nostr\EventInterface;
use swentel\nostr\MessageInterface;

class EventMessage implements MessageInterface
{
    /**
     * Message type.
     *
     * @var string
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
        $this->setType(MessageTypeEnum::EVENT);
    }

    public function setType(MessageTypeEnum $type): void
    {
        $this->type = $type->value;
    }

    private function getType(): string
    {
        return $this->type;
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
