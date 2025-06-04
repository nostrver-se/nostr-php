<?php

declare(strict_types=1);

namespace swentel\nostr\Event\List;

use swentel\nostr\Event\Event;
use swentel\nostr\Filter\Filter;
use swentel\nostr\Message\RequestMessage;
use swentel\nostr\Relay\Relay;
use swentel\nostr\RelayResponse\RelayResponseEose;
use swentel\nostr\RelayResponse\RelayResponseEvent;
use swentel\nostr\Request\Request;
use swentel\nostr\Subscription\Subscription;

/**
 * DmRelaysList class for DM relays.
 * To fetch the relays where to send NIP-17 direct messages of a given pubkey.
 * Described in NIP-17 and NIP-51.
 */
class DmRelaysList extends Event
{
    /**
     * Event kind 10050.
     *
     * @var int
     */
    protected int $kind = 10050;

    /**
     * @var array
     */
    protected array $relays = [];

    /**
     * Constructor.
     */
    public function __construct()
    {
        parent::__construct();
        if ($this->kind !== 10050) {
            throw new \RuntimeException('You cannot set the kind number of ' . __CLASS__ . ' which is fixed to ' . $this->kind);
        }
        $this->setKind($this->kind);
    }

    /**
     * Get the DM relays from a given pubkey and optional given relay URL.
     * If the list (array) with relays is empty, other attempts are made with known public relays.
     *
     * @param string $pubkey
     * @param string $relayURL
     * @return array
     */
    public function getRelays(string $pubkey, string $relayURL = 'wss://relay.nostr.band'): array
    {
        /**
         * @TODO
         * Implements fetching the write relays where this pubkey writes to with RelayListMetadata class,
         * so we than know where to possibly find the DM relays list.
         */
        $this->setPublicKey($pubkey);
        $subscription = new Subscription();
        $filter = new Filter();
        $filter->setLimit(1);
        $filter->setKinds([$this->kind]);
        $filter->setAuthors([$pubkey]);
        $requestMessage = new RequestMessage($subscription->getId(), [$filter]);
        $relay = new Relay($relayURL);
        $request = new Request($relay, $requestMessage);
        $response = $request->send();
        foreach ($response as $relayResponses) {
            foreach ($relayResponses as $relayResponse) {
                if ($relayResponse instanceof RelayResponseEvent) {
                    $event = $relayResponse->event;
                    $this->setTags($event->tags);
                    $this->relays = $this->getTag('relay');
                }
            }
        }
        if (empty($this->relays)) {
            // Fallback when no relays are found for given relay URL, let's query other relays.
            $other_relays_to_query = $this->getKnownRelays();
            foreach ($other_relays_to_query as $relay_url) {
                $subscription = new Subscription();
                $requestMessage = new RequestMessage($subscription->getId(), [$filter]);
                $relay->setUrl($relay_url);
                $request = new Request($relay, $requestMessage);
                $response = $request->send();
                foreach ($response as $relayResponses) {
                    foreach ($relayResponses as $relayResponse) {
                        if ($relayResponse instanceof RelayResponseEose) {
                            break;
                        }
                        if ($relayResponse instanceof RelayResponseEvent) {
                            $event = $relayResponse->event;
                            $this->setTags($event->tags);
                            $this->relays = $this->getTag('relay');
                        }
                    }
                }
                if (!empty($this->relays)) {
                    break;
                }
            }
        }
        // Cleaning up relay strings...
        if (!empty($this->relays)) {
            foreach ($this->relays as $index => $relay) {
                if (str_contains($relay[1], ' ') === true) {
                    // Remove spaces
                    $this->relays[$index][1] = str_replace(' ', '', $relay[1]);
                }
            }
        }
        return $this->relays;
    }

    /**
     * Get a list of known (public) relays to query.
     *
     * @return array List of relay URLs
     */
    private function getKnownRelays(): array
    {
        // TODO: This would ideally come from configuration.
        return [
            'wss://relay.damus.io',
            'wss://relay.primal.net',
        ];
    }
}
