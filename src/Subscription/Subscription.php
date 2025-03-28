<?php

declare(strict_types=1);

namespace swentel\nostr\Subscription;

use swentel\nostr\SubscriptionInterface;

class Subscription implements SubscriptionInterface
{
    /**
     * String of max length 64 chars. It represents a subscription per WebSocket connection.
     *
     * @var string
     */
    private string $id;

    /**
     *
     */
    public function __construct()
    {
        $this->id = $this->setId();
    }

    /**
     * @param int $length
     * @return string
     */
    public function setId($length = 64): string
    {
        if ($length > 64) {
            throw new \RuntimeException('Subscription ID is longer than the maximum length of 64 chars.');
        }
        // String of all alphanumeric character
        $str_result = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';
        // Shuffle the $str_result and returns substring of specified length
        return substr(str_shuffle($str_result), 0, $length);
    }

    /**
     * @return string
     */
    public function getId(): string
    {
        return $this->id;
    }
}
