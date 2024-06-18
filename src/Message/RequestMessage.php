<?php

declare(strict_types=1);

namespace swentel\nostr\Message;

use swentel\nostr\MessageInterface;
use swentel\nostr\Filter;

class RequestMessage implements MessageInterface
{
    /**
     * An arbitrary, non-empty string of max length 64 chars
     */
    protected string $subscriptionId;

    /**
     * Array of filters
     */
    protected array $filters = [];

    /**
     * Constructor for the RequestMessage class.
     * Initializes the subscription ID and filters array based on the provided parameters.
     *
     * @param string $subscriptionId The ID of the subscription
     * @param array $filters An array of filters to be applied
     */
    public function __construct(string $subscriptionId, array $filters)
    {
        $this->subscriptionId = $subscriptionId;
        foreach($filters as $filter) {
            $this->filters[] = $filter->toArray();
        }
    }

    /**
     * Generates a JSON-encoded request array by merging the subscription ID and filters array.
     *
     * @return string The JSON-encoded request array
     */
    public function generate(): string
    {
        $requestArray = array_merge(["REQ", $this->subscriptionId], $this->filters);
        return json_encode($requestArray, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }
}
