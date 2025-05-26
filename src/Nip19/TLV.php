<?php

declare(strict_types=1);

namespace swentel\nostr\Nip19;

use swentel\nostr\Relay\Relay;
use swentel\nostr\Relay\RelaySet;
use swentel\nostr\TLVInterface;

class TLV implements TLVInterface
{
    /**
     * @var string
     */
    public string $id;

    /**
     * @var string|null
     */
    public ?string $dTag;

    /**
     * @var string
     */
    public string $author;

    /**
     * @var RelaySet|null
     */
    public ?RelaySet $relays;

    /**
     * @var int
     */
    public int $kind;

    /**
     * Base constructor.
     *
     * @param string $id
     * @param string|null $dTag
     * @param string $author
     * @param array|null $relays
     * @param int $kind
     */
    public function __construct(string $id, ?string $dTag, string $author, ?array $relays, int $kind)
    {
        $this->id = $id;
        $this->dTag = $dTag;
        $this->author = $author;
        $this->setRelays($relays);
        $this->kind = $kind;
    }

    /**
     * {@inheritdoc}
     */
    public function setId(string $id): static
    {
        $this->id = $id;
        return $this;
    }

    /**
     * Get id.
     *
     * @return string
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * {@inheritdoc}
     */
    public function setDTag(?string $dTag): static
    {
        $this->dTag = $dTag;
        return $this;
    }

    /**
     * Get identifier.
     *
     * @return string|null
     */
    public function getDTag(): ?string
    {
        return $this->dTag;
    }

    /**
     * {@inheritdoc}
     */
    public function setAuthor(string $author): static
    {
        $this->author = $author;
        return $this;
    }

    /**
     * Get author.
     *
     * @return string
     */
    public function getAuthor(): string
    {
        return $this->author;
    }

    /**
     * {@inheritdoc}
     */
    public function setRelays(array $relays): static
    {
        if (empty($relays)) {
            $this->relays = null;
        } else {
            $relaySet = new RelaySet();
            foreach ($relays as $relayUrl) {
                if ($relayUrl instanceof Relay) {
                    $relaySet->addRelay($relayUrl);
                } else {
                    $relay = new Relay($relayUrl);
                    $relaySet->addRelay($relay);
                }
            }
            $this->relays = $relaySet;
        }
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getRelays(bool $toArray = true): RelaySet|array|null
    {
        if ($toArray && !is_null($this->relays)) {
            $relaysArray = [];
            if ($relays = $this->relays->getRelays()) {
                foreach ($relays as $relay) {
                    if ($relay instanceof Relay) {
                        $relaysArray[] = $relay->getUrl();
                    }
                }
            }
            return $relaysArray;
        }
        return $this->relays;
    }

    /**
     * {@inheritdoc}
     */
    public function setKind(int $kind): static
    {
        $this->kind = $kind;
        return $this;
    }

    /**
     * Get kind number.
     *
     * @return int
     */
    public function getKind(): int
    {
        return $this->kind;
    }
}
