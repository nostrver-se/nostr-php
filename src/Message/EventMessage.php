<?php

namespace swentel\nostr\Message;

use swentel\nostr\EventInterface;
use swentel\nostr\MessageInterface;

class EventMessage implements MessageInterface
{

    /**
     * The event.
     *
     * @var EventInterface
     */
    protected EventInterface $event;

    public function __construct(EventInterface $event) {
        $this->event = $event;
    }

    /**
     * {@inheritdoc}
     */
    public function generate(): string
    {
        return '["EVENT", ' . json_encode($this->event->toArray(), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . ']';
    }

}