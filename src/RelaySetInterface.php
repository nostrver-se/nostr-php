<?php

declare(strict_types=1);

namespace swentel\nostr;

use swentel\nostr\Relay\CommandResult;
use swentel\nostr\Relay\Relay;

interface RelaySetInterface
{
    /**
     * Set relays in this set.
     *
     * @param array $relays
     * @return void
     */
    public function setRelays(array $relays): void;
    /**
     * Get all relays.
     *
     * @return array
     */
    public function getRelays(): array;
    /**
     * Add relay to this set.
     *
     * @param Relay $relay
     * @return void
     */
    public function addRelay(Relay $relay): void;
    /**
     * Remove relay from this set.
     *
     * @param Relay $relay
     * @return void
     */
    public function removeRelay(Relay $relay): void;

    /**
     * The message to be sent to the relays.
     *
     * @param MessageInterface $message
     * @return void
     */
    public function setMessage(MessageInterface $message): void;
    /**
     * Create a relay set from a list of relay URLs.
     *
     * @param string|array $urls
     * @return void
     */
    public function createFromUrls(string|array $urls): void;
    /**
     * Connect to all relays in this set.
     *
     * @param bool $throwOnErrorx
     *   If true, throw an exception if any relay fails to connect.
     *   If false, return false if any relay fails to connect.
     * @return bool
     */
    public function connect($throwOnError = true): bool;
    /**
     * Disconnect all relays in this set.
     *
     * @return bool
     */
    public function disconnect(): bool;
    /**
     * All are relays connected?
     *
     * @return bool
     */
    public function isConnected(): bool;

    /**
     *  Sends the message to all the relays in this set.
     *
     * @return array
     *   Return an array with the results of each relay.
     */
    public function send(): array;
}
