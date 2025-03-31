<?php

declare(strict_types=1);

namespace swentel\nostr\Event\Profile;

use swentel\nostr\Event\Event;
use swentel\nostr\Filter\Filter;
use swentel\nostr\Message\RequestMessage;
use swentel\nostr\Relay\Relay;
use swentel\nostr\RelayResponse\RelayResponseEvent;
use swentel\nostr\Request\Request;
use swentel\nostr\Subscription\Subscription;

/**
 * Profile class for event kind 0 user metadata.
 */
class Profile extends Event
{

    /**
     * Event kind 0.
     *
     * @var int
     */
    protected int $kind = 0;

    /**
     * nickname or full name
     *
     * @var string
     */
    public string $name;

    /**
     * Short bio.
     *
     * @var string
     */
    public string $about;

    /**
     * URL of image.
     *
     * @var string
     */
    public string $picture;

    /**
     * Base Constructor for Profile objects.
     */
    public function __construct()
    {
        parent::__construct();
        if ($this->kind !== 0) {
            throw new \RuntimeException('You cannot set the kind number of ' . __CLASS__ . ' which is fixed to 0');
        }
        $this->setKind($this->kind);
    }

    /**
     * Fetch profile / user metadata from a relay.
     *
     * @param string $pubkey
     *   Pubkey to fetch the data from.
     * @param string $relayURL
     *   Relay to fetch from, defaults to wss://purplepag.es
     * @return $this
     */
    public function fetch(string $pubkey, string $relayURL = 'wss://purplepag.es'): Profile
    {
        $this->setPublicKey($pubkey);
        $subscription = new Subscription();
        $filter = new Filter();
        $filter->setLimit(1);
        $filter->setKinds([0]);
        $filter->setAuthors([$pubkey]);
        $requestMessage = new RequestMessage($subscription->getId(), [$filter]);
        $relay = new Relay($relayURL);
        $request = new Request($relay, $requestMessage);
        $response = $request->send();
        foreach ($response as $relayResponses) {
            foreach ($relayResponses as $relayResponse) {
                if ($relayResponse instanceof RelayResponseEvent) {
                    $event = $relayResponse->event;
                    $this->setId($event->id);
                    $this->setSignature($event->sig);
                    $this->setContent($event->content);
                    $content = json_decode($event->content);
                    $this->setName($content->name);
                    $this->setAbout($content->about);
                    $this->setPicture($content->picture);
                }
            }
        }
        return $this;
    }

    /**
     * Set name.
     *
     * @param string $name
     * @return $this
     */
    public function setName(string $name): static
    {
        $this->name = $name;
        return $this;
    }

    /**
     * Set about text.
     *
     * @param string $about
     * @return $this
     */
    public function setAbout(string $about): static
    {
        $this->about = $about;
        return $this;
    }

    /**
     * Set picture URL.
     *
     * @param string $picture
     * @return $this
     */
    public function setPicture(string $picture): static
    {
        $this->picture = $picture;
        return $this;
    }

    public function setContent(string $content): static
    {
        if ($content === '') {
            $content_array = [
                'name' => $this->name,
                'about' => $this->about,
                'picture' => $this->picture,
            ];
            $this->content = json_encode($content_array);
        } else {
            $this->content = $content;
        }
        return $this;
    }

    /**
     * Stringified JSON object containing name, about and the profile picture.
     *
     * @return string
     */
    public function getContent(): string
    {
        return $this->content;
    }

    /**
     * TODO add extra metadata fields
     * See https://nips.nostr.com/24#kind-0
     */
}
