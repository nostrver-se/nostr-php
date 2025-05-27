<?php

declare(strict_types=1);

namespace swentel\nostr\Event;

use Mdanter\Ecc\Crypto\Signature\SchnorrSignature;
use PhpCsFixer\DocBlock\Tag;
use swentel\nostr\EventInterface;

/**
 * Generic Nostr event class.
 */
class Event implements EventInterface
{
    /**
     * The event kind which is an integer between 0 and 65535.
     *
     * Override this property in your custom events to set the value
     * immediately.
     *
     * @var int
     */
    protected int $kind = 0;

    /**
     * The event id.
     * 32-bytes lowercase hex-encoded sha256 of the serialized event data.
     *
     * @var string $id
     */
    protected string $id = '';

    /**
     * The event signature.
     * 64-bytes lowercase hex of the signature of the sha256 hash of the serialized event data, which is the same as the "id" field.
     *
     * @var string
     */
    protected string $sig = '';

    /**
     * 32-bytes lowercase hex-encoded public key of the event creator.
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
     * The created at unix timestamp in seconds.
     *
     * @var int
     */
    protected int $created_at = 0;

    /**
     * Tags of the event.
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
        foreach ($tags as $tag) {
            $this->tags[] = $tag;
        }
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function addTag(array $tag): static
    {
        $this->tags[] = $tag;
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
    public function setTag(string $key, array $value): static
    {
        // Check if the key already exists in any of the tags
        $keyExists = false;
        foreach ($this->tags as $index => $tag) {
            if ($tag[0] === $key) {
                // Key exists, update the tag
                $this->tags[$index] = array_merge([$key], $value);
                $keyExists = true;
                break;
            }
        }

        // If key doesn't exist, add a new tag
        if (!$keyExists) {
            $this->tags[] = array_merge([$key], $value);
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getTag(string $key): array
    {
        $tags = [];
        foreach ($this->tags as $tag) {
            if ($tag[0] === $key) {
                $tags[] = $tag;
            }
        }
        return $tags;
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
            if (in_array($key, $ignore_properties)) {
                continue;
            }
            $array[$key] = $val;
        }
        return $array;
    }

    /**
     * {@inheritdoc}
     */
    public function toJson(): string
    {
        return json_encode($this->toArray(), JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }

    /**
     * {@inheritdoc}
     */
    public function populate(object $input): static
    {
        $this->setContent($input->content);
        $this->setCreatedAt($input->created_at);
        $this->setId($input->id);
        $this->setKind($input->kind);
        $this->setPublicKey($input->pubkey);
        $this->setSignature($input->sig);
        if (!empty($input->tags)) {
            $this->setTags($input->tags);
        }
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function verify(string|object $input = ''): bool
    {
        try {
            if ($input === '') {
                $event = json_decode(
                    $this->toJson(),
                    flags: \JSON_UNESCAPED_SLASHES | \JSON_UNESCAPED_UNICODE | \JSON_THROW_ON_ERROR,
                );
            } elseif (is_string($input)) {
                $event = json_decode(
                    $input,
                    flags: \JSON_UNESCAPED_SLASHES | \JSON_UNESCAPED_UNICODE | \JSON_THROW_ON_ERROR,
                );
            } else {
                $event = $input;
            }
        } catch (\JsonException) {
            return false;
        }

        if (!$event instanceof \stdClass
            || !property_exists($event, 'id')
            || !property_exists($event, 'pubkey')
            || !property_exists($event, 'created_at')
            || !property_exists($event, 'kind')
            || !property_exists($event, 'tags')
            || !property_exists($event, 'content')
            || !property_exists($event, 'sig')
            || !is_string($event->id)
            || !is_string($event->pubkey)
            || !is_int($event->created_at)
            || !is_int($event->kind)
            || !is_array($event->tags)
            || !is_string($event->content)
            || !is_string($event->sig)
        ) {
            return false;
        }

        if (!empty($event->tags)) {
            foreach ($event->tags as $tag) {
                if (!is_array($tag)) {
                    return false;
                }

                foreach ($tag as $value) {
                    if (!is_string($value)) {
                        return false;
                    }
                }
            }
        }

        try {
            $computedId = hash(
                'sha256',
                json_encode(
                    [
                        0,
                        $event->pubkey,
                        $event->created_at,
                        $event->kind,
                        $event->tags,
                        $event->content,
                    ],
                    \JSON_UNESCAPED_SLASHES | \JSON_UNESCAPED_UNICODE,
                ),
            );
        } catch (\JsonException) {
            return false;
        }

        if (!hash_equals($computedId, $event->id)) {
            return false;
        }

        return (new SchnorrSignature())->verify($event->pubkey, $event->sig, $event->id);
    }

    /**
     * {@inheritdoc}
     */
    public function isRegular(): bool
    {
        if (
            ($this->getKind() >= 4 &&  $this->getKind() < 45)
            || ($this->getKind() >= 1000 &&  $this->getKind() < 10000)
            || $this->getKind() === 1
            || $this->getKind() === 2
        ) {
            return true;
        }
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function isReplaceable(): bool
    {
        if (
            ($this->getKind() >= 10000 &&  $this->getKind() < 20000)
            || $this->getKind() === 0
            || $this->getKind() === 3
        ) {
            return true;
        }
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function isEphemeral(): bool
    {
        if ($this->getKind() >= 20000 &&  $this->getKind() < 30000) {
            return true;
        }
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function isAddressable(): bool
    {
        if ($this->getKind() >= 30000 &&  $this->getKind() < 40000) {
            return true;
        }
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public static function fromVerified(string|object $input): ?static
    {
        $event = new static();
        if (!$event->verify($input)) {
            return null;
        }

        try {
            if (is_string($input)) {
                $data = json_decode($input, flags: \JSON_THROW_ON_ERROR);
            } else {
                $data = $input;
            }

            $event->setId($data->id)
                ->setPublicKey($data->pubkey)
                ->setCreatedAt($data->created_at)
                ->setKind($data->kind)
                ->setContent($data->content)
                ->setSignature($data->sig)
                ->setTags($data->tags);

            return $event;
        } catch (\Throwable) {
            return null;
        }
    }
}
