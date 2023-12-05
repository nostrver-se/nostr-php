<?php

declare(strict_types=1);

namespace swentel\nostr\Relay;

use swentel\nostr\CommandResultInterface;

class CommandResult implements CommandResultInterface
{
    /**
     * Whether the request was successful or not.
     *
     * @var bool
     */
    protected bool $success = false;

    /**
     * The message.
     *
     * @var string
     */
    protected string $message = '';

    /**
     * The event ID.
     *
     * @var mixed|string
     */
    protected mixed $eventId = '';

    /**
     * Constructs the Relay Response.
     *
     * @param array $response
     */
    public function __construct(array $response)
    {
        if ($response[0] === 'OK' && $response[2] === true && !str_starts_with($response[3], 'duplicate:')) {
            $this->success = true;
            $this->eventId = $response[1];
        } else {
            $this->message = !empty($response[3]) ? $response[3] : 'Failed with no reason';
        }
    }

    /**
     * {@inheritdoc}
     */
    public function isSuccess(): bool
    {
        return $this->success === true;
    }

    /**
     * {@inheritdoc}
     */
    public function message(): string
    {
        return $this->message;
    }

    /**
     * {@inheritdoc}
     */
    public function getEventId(): string
    {
        return $this->eventId;
    }
}
