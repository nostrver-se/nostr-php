<?php

declare(strict_types=1);

namespace swentel\nostr\Filter;

use swentel\nostr\FilterInterface;
use swentel\nostr\Key\Key;

#[\AllowDynamicProperties]
class Filter implements FilterInterface
{
    /**
     * A list of event ids
     */
    public array $ids;

    /**
     * A list of lowercase pubkeys, the pubkey of an event must be one of these
     */
    public array $authors;

    /**
     * A list of a kind numbers
     */
    public array $kinds;

    /**
     * A list of tags values starting with a # followed by single letters (format: #<single-letter (a-zA-Z)>).
     */
    public array $tags;

    /**
     * A list of #e tag values (list of event ids)
     */
    public array $etags;

    /**
     * A list of #p tag values (list of pubkeys).
     */
    public array $ptags;

    /**
     * An integer unix timestamp in seconds, events must be newer than this to pass
     */
    public int $since;

    /**
     * An integer unix timestamp in seconds, events must be older than this to pass
     */
    public int $until;

    /**
     * Maximum number of events relays SHOULD return in the initial query
     */
    public int $limit;

    /**
     * Set the ids for filtering multiple events.
     *
     * @param array $ids
     * @return $this
     */
    public function setIds(array $ids): static
    {
        $this->ids = $ids;
        return $this;
    }

    /**
     * Set the authors for the Filter object.
     *
     * @param array $pubkeys
     *   The array of authors to set.
     */
    public function setAuthors(array $pubkeys): static
    {
        // Loop over given values in the pubkeys array.
        foreach ($pubkeys as $index => $key) {
            // If $pubkey string starts with `npub` let's try to convert it to a pubkey.
            if (str_starts_with($key, 'npub')) {
                $npub = new Key();
                $key = $npub->convertToHex($key);
                $pubkeys[$index] = $key;
            }
            if (!$this->isLowercaseHex($key)) {
                throw new \RuntimeException("Author pubkeys must be an array of 64-character lowercase hex values");
            }
            if (count($pubkeys) !== count(array_unique($pubkeys))) {
                throw new \RuntimeException("There are duplicate author pubkeys in the filter.");
            }
            // Add key to array.
            $this->authors[] = $key;
        }
        return $this;
    }

    /**
     * Set the kinds for the Filter object.
     *
     * @param array $kinds
     *   The array of kinds to set.
     */
    public function setKinds(array $kinds): static
    {
        $this->kinds = $kinds;
        return $this;
    }

    /**
     * Set tags for the Filter object.
     * Every tag in this filter property needs to start with a #.
     *
     * @param array $tags
     *   The array of tags to set.
     * @return Filter
     */
    public function setTags(array $tags): static
    {
        foreach ($tags as $name => $value) {
            if (!is_array($value)) {
                $message = sprintf('Provided tag value for %s must be an array', $name);
                throw new \RuntimeException($message);
            }
            $this->validateTagName($name);
            $this->setTag($name, $value);
        }
        return $this;
    }

    /**
     * Set a single tag value for the Filter object.
     *
     * @param string $name
     *   Tag name.
     * @param string $value
     *   Tag value.
     * @return Filter
     */
    public function setTag(string $name, array $value): static
    {
        $this->validateTagName($name);
        if (isset($this->{$name})) {
            $this->{$name} = array_merge($this->{$name}, $value);
        } else {
            $this->{$name} = $value;
        }
        return $this;
    }

    /**
     * Validate standardized tag.
     *
     * @param $tag
     *   Provided tag name to be validated.
     * @return void
     */
    private function validateTagName($tag): void
    {
        // Check if tag starts with #.
        if (!str_starts_with($tag, '#')) {
            throw new \RuntimeException('All tags on a filter must start with #');
        }
        // Check if tag has valid value.
        $pattern = '/^#[a-z_-]+$/i';
        if (!preg_match($pattern, $tag)) {
            $message = sprintf('Invalid tag provided: %s', $tag);
            throw new \RuntimeException($message);
        }
    }

    /**
     * Set the #e tag for the Filter object.
     *
     * @param array $etags
     *   The array of tag to set.
     * @return Filter
     */
    public function setLowercaseETags(array $etags): static
    {
        foreach ($etags as $tag) {
            if (!$this->isLowercaseHex($tag)) {
                throw new \RuntimeException("#e tags must be an array of 64-character lowercase hex values");
            }
        }
        $this->etags = $etags;
        return $this;
    }

    /**
     * Set the #p tag for the Filter object.
     *
     * @param array $ptags
     *   The array of tag to set.
     * @return Filter
     */
    public function setLowercasePTags(array $ptags): static
    {
        // Check IF array contain exact 64-character lowercase hex values
        foreach ($ptags as $tag) {
            if (!$this->isLowercaseHex($tag)) {
                throw new \RuntimeException("#p tags must be an array of 64-character lowercase hex values");
            }
        }
        $this->ptags = $ptags;
        return $this;
    }

    /**
     * Set since parameter for the Filter object.
     *
     * @param int $since
     *   The limit to set.
     */
    public function setSince(int $since): static
    {
        if (!$this->isValidTimestamp($since)) {
            throw new \RuntimeException("The provided since filter is not a valid timestamp");
        }
        $this->since = $since;
        return $this;
    }

    /**
     * Set the until for the Filter object.
     *
     * @param int $until
     *   The limit to set.
     */
    public function setUntil(int $until): static
    {
        if (!$this->isValidTimestamp($until)) {
            throw new \RuntimeException("The provided until filter is not a valid timestamp");
        }
        $this->until = $until;
        return $this;
    }

    /**
     * Set the limit for the Filter object.
     *
     * @param int $limit
     *   The limit to set.
     */
    public function setLimit(int $limit): static
    {
        $this->limit = $limit;
        return $this;
    }

    /**
     * Check if a given string is a 64-character lowercase hexadecimal value.
     *
     * @param string $string
     *   The string to check.
     * @return bool
     *   True if the string is a 64-character lowercase hexadecimal value, false otherwise.
     */
    public function isLowercaseHex($string): bool
    {
        // Regular expression to match 64-character lowercase hexadecimal value
        $pattern = '/^[a-f0-9]{64}$/';
        // Check if the string matches the pattern
        return preg_match($pattern, $string) === 1;
    }

    /**
     * Check if a given timestamp is valid.
     *
     * @param mixed $timestamp
     *   The timestamp to check.
     * @return bool
     *   True if the timestamp is valid, false otherwise.
     */
    public function isValidTimestamp($timestamp): bool
    {
        // Convert the timestamp to seconds
        $timestamp = (int) $timestamp;
        // Check if the timestamp is valid
        return ($timestamp !== 0 && $timestamp !== false && $timestamp !== -1);
    }

    /**
     * Return an array representation of the object by iterating through its properties.
     *
     * @return array
     *   The array representation of the object.
     */
    public function toArray(): array
    {
        $array = [];
        foreach (get_object_vars($this) as $key => $val) {
            if ($key === 'etags') {
                $array['#e'] = $val;
            } elseif ($key === 'ptags') {
                $array['#p'] = $val;
            } else {
                $array[$key] = $val;
            }
        }
        return $array;
    }
}
