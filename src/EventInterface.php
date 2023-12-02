<?php

declare(strict_types=1);

namespace swentel\nostr;

interface EventInterface
{
    /**
     * Set the id.
     *
     * @param string $id
     *
     * @return $this
     */
    public function setId(string $id): static;

    /**
     * Get the Id.
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
     * Add an event tag.
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
     */
    public function toJson(): string;

    /**
     * Returns true if event object encodes to a valid Nostr event JSON string.
     *
     * @return bool
     */
    public function verify(): bool;

}
