<?php

declare(strict_types=1);

namespace swentel\nostr;

interface FilterInterface
{
    /**
     * Set the authors for the Filter object.
     *
     * @param array $pubkey The array of authors to set.
     */
    public function setAuthors(array $pubkey): static;

    /**
     * Set the kinds for the Filter object.
     *
     * @param array $kinds The array of kinds to set.
     */
    public function setKinds(array $kinds): static;

    /**
     * Set the tag for the Filter object.
     *
     * @param array $tag The array of tag to set.
     */
    public function setLowercaseETags(array $tag): static;

    /**
     * Set the #p tag for the Filter object.
     *
     * @param array $ptag The array of tag to set.
     */
    public function setLowercasePTags(array $ptag): static;

    /**
     * Set the since for the Filter object.
     *
     * @param int $since The limit to set.
     */
    public function setSince(int $since): static;

    /**
     * Set the until for the Filter object.
     *
     * @param int $until The limit to set.
     */
    public function setUntil(int $until): static;

    /**
     * Set the limit for the Filter object.
     *
     * @param int $limit The limit to set.
     */
    public function setLimit(int $limit): static;

    /**
     * Check if a given string is a 64-character lowercase hexadecimal value.
     *
     * @param string $string The string to check.
     * @return bool True if the string is a 64-character lowercase hexadecimal value, false otherwise.
     */
    public function isLowercaseHex($string): bool;

    /**
     * Check if a given timestamp is valid.
     *
     * @param mixed $timestamp The timestamp to check.
     * @return bool True if the timestamp is valid, false otherwise.
     */
    public function isValidTimestamp($timestamp): bool;

    /**
     * Return an array representation of the object by iterating through its properties.
     *
     * @return array The array representation of the object.
     */
    public function toArray(): array;
}
