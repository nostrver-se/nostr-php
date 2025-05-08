<?php

declare(strict_types=1);

namespace swentel\nostr;

interface RelayInterface
{
    /**
     * Set URL of the relay.
     *
     * @param string $url
     * @return void
     */
    public function setUrl(string $url): void;
    /**
     * Get URL of the relay.
     *
     * @return string
     */
    public function getUrl(): string;

    /**
     * Set message that will be sent to the relay.
     *
     * @param MessageInterface $message
     * @return void
     */
    public function setMessage(MessageInterface $message): void;

    /**
     * Sends the message to the relay.
     *
     * @return RelayResponseInterface
     */
    public function send(): RelayResponseInterface;


    /**
     * Connects to the relay.
     *
     * @return void
     */
    public function connect(): void;

    /**
     * Checks if the connection to the relay is established.
     *
     * @return bool
     */
    public function isConnected(): bool;

    /**
     * Disconnects from the relay.
     *
     * @return void
     */
    public function disconnect(): void;

}
