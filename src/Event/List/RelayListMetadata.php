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
 * Relay List Metadata
 * To fetch the relays where to write to and read from of a given pubkey.
 * Described in NIP-65.
 */
class RelayListMetadata extends Event
{
    /**
     * Event kind 10002.
     *
     * @var int
     */
    protected int $kind = 10002;

    /**
     * @var array
     */
    protected array $relays = [];

    /**
     * Constructor.
     */
    public function __construct(string $pubkey, string $relayURL = 'wss://purplepag.es')
    {
        parent::__construct();
        if ($this->kind !== 10002) {
            throw new \RuntimeException('You cannot set the kind number of ' . __CLASS__ . ' which is fixed to ' . $this->kind);
        }
        $this->setKind($this->kind);
        $this->fetch($pubkey, $relayURL);
    }

    /**
     * Get all relays.
     *
     * @return array
     */
    public function getRelays(): array
    {
        if (empty($this->relays)) {
            return [];
        }
        foreach ($this->relays as $relay) {
            if (!preg_match('/^(ws|wss):\/\//', $relay[1])) {
                throw new \InvalidArgumentException('Invalid URL format. URL ' . $relay[1] . ' must start with ws:// or wss://');
            }
        }
        return $this->relays;
    }

    /**
     * Get relays where the npub writes to.
     *
     * @return array
     */
    public function getWriteRelays(): array
    {
        if (empty($this->relays)) {
            throw new \RuntimeException('The relays property is empty of ' . __CLASS__);
        }
        $writeRelays = [];
        foreach ($this->relays as $relay) {
            if (!preg_match('/^(ws|wss):\/\//', $relay[1])) {
                throw new \InvalidArgumentException('Invalid URL format. URL ' . $relay[1] . ' must start with ws:// or wss://');
            }
            if (!isset($relay[2]) && str_starts_with($relay[1], 'wss://')) {
                $writeRelays[] = $relay[1];
            }
            if (in_array('write', $relay, true)) {
                $writeRelays[] = $relay[1];
            }
        }
        return $writeRelays;
    }

    /**
     * Get relays where the npub reads from.
     *
     * @return array
     */
    public function getReadRelays(): array
    {
        if (empty($this->relays)) {
            throw new \RuntimeException('The relays property is empty of ' . __CLASS__);
        }
        $readRelays = [];
        foreach ($this->relays as $relay) {
            if (!preg_match('/^(ws|wss):\/\//', $relay[1])) {
                throw new \InvalidArgumentException('Invalid URL format. URL ' . $relay[1] . ' must start with ws:// or wss://');
            }
            if (!isset($relay[2]) && str_starts_with($relay[1], 'wss://')) {
                $readRelays[] = $relay[1];
            }
            if (in_array('read', $relay, true)) {
                $readRelays[] = $relay[1];
            }
        }
        return $readRelays;
    }

    /**
     * Fetch all relays from a given pubkey and optional given relay URL.
     * If the list (array) with relays is empty, other attempts are made with known public relays.
     *
     * @param string $pubkey
     * @param string $relayURL
     * @return array
     */
    private function fetch(string $pubkey, string $relayURL = 'wss://purplepag.es'): void
    {
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
                    $this->relays = $this->getTag('r');
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
                            $this->relays = $this->getTag('r');
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
    }

    /**
     * Get a list of known (public) relays to query which indexes events with kind 10002.
     *
     * @return array List of relay URLs
     */
    private function getKnownRelays(): array
    {
        // TODO: This would ideally come from configuration.
        return [
            'wss://profiles.nostrver.se',
            'wss://indexer.coracle.social',
            'wss://profiles.nostr1.com',
            'wss://relay.nostr.band',
        ];
    }
}
