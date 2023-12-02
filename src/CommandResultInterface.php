<?php

declare(strict_types=1);

namespace swentel\nostr;

interface CommandResultInterface
{
    /**
     * Returns whether the request was successful.
     *
     * @return bool
     */
    public function isSuccess(): bool;

    /**
     * Returns the message, if any.
     *
     * @return string
     */
    public function message(): string;

    /**
     * Returns the event id.
     *
     * @return string
     */
    public function getEventId(): string;
}
