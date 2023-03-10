<?php

namespace swentel\nostr\Event;

use swentel\nostr\EventInterface;

class Event implements EventInterface
{

    /**
     * The event kind.
     *
     * Override this property in your custom events to set the value
     * immediately.
     *
     * @var int
     */
    protected int $kind = 0;

    /**
     * The event id.
     *
     * @var string
     */
    protected string $id = '';

    /**
     * The event signature.
     *
     * @var string
     */
    protected string $sig = '';

    /**
     * The public key.
     *
     * @var string
     */
    protected string $pubkey;

    /**
     * The event content.
     *
     * @var string
     */
    protected string $content = '';

    /**
     * The created at timestamp.
     *
     * @var int
     */
    protected int $created_at = 0;

    /**
     * The event tags.
     *
     * @var array
     */
    protected array $tags = [];

    /**
     * Base constructor for events.
     */
    public function __construct()
    {
        $this->setCreatedAt(time());
        $this->setKind($this->kind);
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
     * {@inheritdoc}
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * {@inheritdoc}
     */
    public function setPublicKey(string $public_key): static
    {
        $this->pubkey = $public_key;
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getPublicKey(): string
    {
        return $this->pubkey;
    }

    /**
     * {@inheritdoc}
     */
    public function setSignature(string $sig): static
    {
        $this->sig = $sig;
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getSignature(): string
    {
        return $this->sig;
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
     * {@inheritdoc}
     */
    public function getKind(): int
    {
       return $this->kind;
    }

    /**
     * {@inheritdoc}
     */
    public function setContent(string $content): static
    {
        $this->content = $content;
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getContent(): string
    {
        return $this->content;
    }

    /**
     * {@inheritdoc}
     */
    public function setTags(array $tags): static
    {
        $this->tags = $tags;
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function addTag($key, $value): static
    {
        $this->tags[$key] = $value;
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getTags(): array
    {
        return $this->tags;
    }

    /**
     * {@inheritdoc}
     */
    public function setCreatedAt(int $time): static
    {
        $this->created_at = $time;
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getCreatedAt(): int
    {
        return $this->created_at;
    }

    /**
     * {@inheritdoc}
     */
    public function toArray(array $ignore_properties = []): array
    {
        $array = [];
        foreach (get_object_vars($this) as $key => $val) {
            if (in_array($key, $ignore_properties))
            {
                continue;
            }
            $array[$key] = $val;
        }
        return $array;
    }

}