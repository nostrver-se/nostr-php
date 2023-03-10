<?php

namespace swentel\nostr\Relay;

use swentel\nostr\CommandResultInterface;

class CommandResult implements CommandResultInterface
{

    /**
     * Whether the request was successful or not.
     *
     * @var bool
     */
    protected bool $success = FALSE;

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
        if ($response[0] == 'OK' && $response[2] && !str_starts_with($response[3], 'duplicate:')) {
            $this->success = TRUE;
            $this->eventId = $response[1];
        }
        else
        {
            $this->message = !empty($resonse[3]) ? $resonse[3] : 'Failed with no reason';
        }
    }

    /**
     * {@inheritdoc}
     */
    public function isSuccess(): bool
    {
        return $this->success === TRUE;
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
