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
     * @var string|null
     */
    public ?string $name = null;

    /**
     * Short bio.
     *
     * @var string|null
     */
    public ?string $about = null;

    /**
     * URL of image.
     *
     * @var string|null
     */
    public ?string $picture = null;

    /**
     * Preferred display name.
     *
     * @var string|null
     */
    public ?string $display_name = null;

    /**
     * NIP-05 identifier.
     *
     * @var string|null
     */
    public ?string $nip05 = null;

    /**
     * Profile banner image URL.
     *
     * @var string|null
     */
    public ?string $banner = null;

    /**
     * User's website URL.
     *
     * @var string|null
     */
    public ?string $website = null;

    /**
     * LNURL for Lightning Address (NIP-57).
     *
     * @var string|null
     */
    public ?string $lud06 = null;

    /**
     * Lightning Address (NIP-57).
     *
     * @var string|null
     */
    public ?string $lud16 = null;

    /**
     * Base Constructor for Profile objects.
     */
    public function __construct()
    {
        parent::__construct();
        if ($this->kind !== 0) {
            throw new \RuntimeException('You cannot set the kind number of ' . __CLASS__ . ' which is fixed to ' . $this->kind);
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
     * @throws \JsonException
     */
    public function fetch(string $pubkey, string $relayURL = 'wss://purplepag.es'): Profile
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
                    $this->setId($event->id);
                    $this->setSignature($event->sig);
                    $this->setContent($event->content);
                    $this->parseProfile($event->content);
                }
            }
        }
        return $this;
    }

    /**
     * Parse a string that contains profile content.
     *
     * @param string $content
     * @throws \JsonException
     * @return $this
     */
    public function parseProfile(string $content): Profile
    {
        $content = json_decode($content, null, 512, JSON_THROW_ON_ERROR);

        // Handle required fields (null if not present)
        $this->setName(isset($content->name) ? $content->name : null);
        $this->setAbout(isset($content->about) ? $content->about : null);
        $this->setPicture(isset($content->picture) ? $content->picture : null);

        // Handle deprecated fields
        if (!isset($content->name) && isset($content->username)) {
            $this->setName($content->username);
        }

        // Handle optional fields
        $this->setDisplayName(isset($content->display_name) ? $content->display_name : null);
        $this->setNip05(isset($content->nip05) ? $content->nip05 : null);
        $this->setBanner(isset($content->banner) ? $content->banner : null);
        $this->setWebsite(isset($content->website) ? $content->website : null);
        $this->setLud06(isset($content->lud06) ? $content->lud06 : null);
        $this->setLud16(isset($content->lud16) ? $content->lud16 : null);

        return $this;
    }

    /**
     * Set name.
     *
     * @param string|null $name
     * @return $this
     */
    public function setName(?string $name): static
    {
        $this->name = $name;
        return $this;
    }

    /**
     * Set about text.
     *
     * @param string|null $about
     * @return $this
     */
    public function setAbout(?string $about): static
    {
        $this->about = $about;
        return $this;
    }

    /**
     * Set picture URL.
     *
     * @param string|null $picture
     * @return $this
     */
    public function setPicture(?string $picture): static
    {
        $this->picture = $picture;
        return $this;
    }

    /**
     * Set display name.
     *
     * @param string|null $display_name
     * @return $this
     */
    public function setDisplayName(?string $display_name): static
    {
        $this->display_name = $display_name;
        return $this;
    }

    /**
     * Set NIP-05 identifier.
     *
     * @param string|null $nip05
     * @return $this
     */
    public function setNip05(?string $nip05): static
    {
        $this->nip05 = $nip05;
        return $this;
    }

    /**
     * Set banner URL.
     *
     * @param string|null $banner
     * @return $this
     */
    public function setBanner(?string $banner): static
    {
        $this->banner = $banner;
        return $this;
    }

    /**
     * Set website URL.
     *
     * @param string|null $website
     * @return $this
     */
    public function setWebsite(?string $website): static
    {
        $this->website = $website;
        return $this;
    }

    /**
     * Set LNURL (NIP-57).
     *
     * @param string|null $lud06
     * @return $this
     */
    public function setLud06(?string $lud06): static
    {
        $this->lud06 = $lud06;
        return $this;
    }

    /**
     * Set Lightning Address (NIP-57).
     *
     * @param string|null $lud16
     * @return $this
     */
    public function setLud16(?string $lud16): static
    {
        $this->lud16 = $lud16;
        return $this;
    }

    public function setContent(string $content): static
    {
        if ($content === '') {
            $content_array = [];

            // Only add fields that are not null
            if ($this->name !== null) {
                $content_array['name'] = $this->name;
            }
            if ($this->about !== null) {
                $content_array['about'] = $this->about;
            }
            if ($this->picture !== null) {
                $content_array['picture'] = $this->picture;
            }
            if ($this->display_name !== null) {
                $content_array['display_name'] = $this->display_name;
            }
            if ($this->nip05 !== null) {
                $content_array['nip05'] = $this->nip05;
            }
            if ($this->banner !== null) {
                $content_array['banner'] = $this->banner;
            }
            if ($this->website !== null) {
                $content_array['website'] = $this->website;
            }
            if ($this->lud06 !== null) {
                $content_array['lud06'] = $this->lud06;
            }
            if ($this->lud16 !== null) {
                $content_array['lud16'] = $this->lud16;
            }

            $this->content = json_encode($content_array);
        } else {
            $this->content = $content;
        }
        return $this;
    }

    /**
     * Stringified JSON object containing profile metadata.
     *
     * @return string
     */
    public function getContent(): string
    {
        return $this->content;
    }
}
