<?php

declare(strict_types=1);

namespace swentel\nostr;

/**
 * Nostr Event interface.
 */
interface EventInterface
{
    /**
     * Set the event id.
     *
     * @param string $id
     *
     * @return $this
     */
    public function setId(string $id): static;

    /**
     * Get the event id.
     *
     * @return string
     */
    public function getId(): string;

    /**
     * Set the signature.
     *
     * @param string $sig
     *
     * @return $this
     */
    public function setSignature(string $sig): static;

    /**
     * Get the signature.
     *
     * @return string
     */
    public function getSignature(): string;

    /**
     * Set the public key.
     *
     * @param string $public_key
     *
     * @return $this
     */
    public function setPublicKey(string $public_key): static;

    /**
     * Get the public key.
     *
     * @return string
     */
    public function getPublicKey(): string;

    /**
     * Set the event kind.
     *
     * @param int $kind
     *
     * @return $this
     */
    public function setKind(int $kind): static;

    /**
     * Returns the kind.
     *
     * @return int
     */
    public function getKind(): int;

    /**
     * Set the event content.
     *
     * @param string $content
     *
     * @return $this
     */
    public function setContent(string $content): static;

    /**
     * Get the event content.
     *
     * @return string
     */
    public function getContent(): string;

    /**
     * Set the event created time.
     * Format is a unix timestamp in seconds.
     *
     * @param int $time
     *
     * @return $this
     */
    public function setCreatedAt(int $time): static;

    /**
     * Get the event created time.
     *
     * @return int
     */
    public function getCreatedAt(): int;

    /**
     * Set the event tags with values.
     *
     * @param array $tags[]
     *   [
     *     ["e", "..."],
     *     ["p", "...", "..."],
     *   ]
     *
     * @return $this
     */
    public function setTags(array $tags): static;

    /**
     * Add a single tag to the event.
     *
     * @param array $tag
     *
     * @return $this
     */
    public function addTag(array $tag): static;

    /**
     * Get the event tags.
     *
     * @return array
     */
    public function getTags(): array;

    /**
     * Get specific tag by key.
     *
     * @param string $key
     * @return array
     */
    public function getTag(string $key): array;

    /**
     * Set a specific tag by key.
     *
     * @param string $key
     * @param array $value
     * @return $this
     */
    public function setTag(string $key, array $value): static;

    /**
     * Convert the object to an array.
     *
     * @param array $ignore_properties
     *   Properties to ignore.
     *
     * @return array
     */
    public function toArray(array $ignore_properties = []): array;

    /**
     * Convert the event object to a JSON string.
     *
     * @return string
     */
    public function toJson(): string;

    /**
     * Populate to a Nostr event object with a given object.
     *
     * @param object $input
     *
     * @return $this
     */
    public function populate(object $input): static;

    /**
     * Returns true if event object encodes to a valid Nostr event JSON string.
     *
     * @param string|object $input
     *   The input to verify.
     *
     * @return bool
     */
    public function verify(string|object $input = ''): bool;

    /**
     * For kind n such that 1000 <= n < 10000 || 4 <= n < 45 || n == 1 || n == 2,
     * events are regular, which means they're all expected to be stored by relays.
     *
     * @return bool
     */
    public function isRegular(): bool;

    /**
     * For kind n such that 10000 <= n < 20000 || n == 0 || n == 3, events are replaceable,
     * which means that, for each combination of pubkey and kind,
     * only the latest event MUST be stored by relays, older versions MAY be discarded.
     *
     * @return bool
     */
    public function isReplaceable(): bool;

    /**
     * For kind n such that 20000 <= n < 30000, events are ephemeral,
     * which means they are not expected to be stored by relays.
     *
     * @return bool
     */
    public function isEphemeral(): bool;

    /**
     * For kind n such that 30000 <= n < 40000, events are addressable by their kind,
     * pubkey and d tag value -- which means that, for each combination of kind,
     * pubkey and the d tag value, only the latest event MUST be stored by relays,
     * older versions MAY be discarded.
     *
     * @return bool
     */
    public function isAddressable(): bool;

    /**
     * Create an Event object from a verified event input.
     *
     * @param string|object $input
     *   The event data as JSON string or decoded object.
     *
     * @return ?static
     *   Returns an Event object if valid, null otherwise.
     */
    public static function fromVerified(string|object $input): ?static;
}
