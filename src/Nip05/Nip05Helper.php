<?php

declare(strict_types=1);

namespace swentel\nostr\Nip05;

/**
 * NIP-05: https://github.com/nostr-protocol/nips/blob/master/05.md
 * Mapping Nostr keys to DNS-based internet identifiers
 */
class Nip05Helper
{
    /**
     * Validates a NIP-05 identifier against a public key.
     *
     * @param string $identifier The NIP-05 identifier (e.g., "bob@example.com")
     * @param string $pubkey The public key to verify against (in hex format)
     * @return bool True if the identifier is valid for the given public key
     */
    public function verify(string $identifier, string $pubkey): bool
    {
        $parts = $this->parseIdentifier($identifier);
        if (!$parts) {
            return false;
        }

        $result = $this->fetchJson($parts['domain'], $parts['name']);
        if (!$result) {
            return false;
        }

        if (!isset($result['names'][$parts['name']])) {
            return false;
        }

        return strtolower($result['names'][$parts['name']]) === strtolower($pubkey);
    }

    /**
     * Gets a public key from a NIP-05 identifier.
     *
     * @param string $identifier The NIP-05 identifier (e.g., "bob@example.com")
     * @return string|null The public key (in hex format) or null if not found
     */
    public function getPublicKey(string $identifier): ?string
    {
        $parts = $this->parseIdentifier($identifier);
        if (!$parts) {
            return null;
        }

        $result = $this->fetchJson($parts['domain'], $parts['name']);
        if (!$result || !isset($result['names'][$parts['name']])) {
            return null;
        }

        return $result['names'][$parts['name']];
    }

    /**
     * Gets relay URLs for a given public key from a NIP-05 identifier.
     *
     * @param string $identifier The NIP-05 identifier (e.g., "bob@example.com")
     * @param string|null $pubkey Optional public key to use instead of looking it up
     * @return array|null An array of relay URLs or null if not found
     */
    public function getRelays(string $identifier, ?string $pubkey = null): ?array
    {
        $parts = $this->parseIdentifier($identifier);
        if (!$parts) {
            return null;
        }

        $result = $this->fetchJson($parts['domain'], $parts['name']);
        if (!$result) {
            return null;
        }

        if (!$pubkey) {
            if (!isset($result['names'][$parts['name']])) {
                return null;
            }
            $pubkey = $result['names'][$parts['name']];
        }

        if (isset($result['relays'][$pubkey]) && is_array($result['relays'][$pubkey])) {
            return $result['relays'][$pubkey];
        }

        return null;
    }

    /**
     * Formats a NIP-05 identifier for display.
     * If the identifier is in the format "_@domain", it will be displayed as just "domain".
     *
     * @param string $identifier The NIP-05 identifier (e.g., "bob@example.com" or "_@example.com")
     * @return string The formatted identifier for display
     */
    public function formatForDisplay(string $identifier): string
    {
        $parts = $this->parseIdentifier($identifier);
        if (!$parts) {
            return $identifier;
        }

        // If it's the special case of "_@domain", display only the domain
        if ($parts['name'] === '_') {
            return $parts['domain'];
        }

        return $identifier;
    }

    /**
     * Parses a NIP-05 identifier into its components.
     *
     * @param string $identifier The NIP-05 identifier (e.g., "bob@example.com")
     * @return array|false Array with 'name' and 'domain' keys or false if invalid
     */
    private function parseIdentifier(string $identifier): array|false
    {
        if (!preg_match('/^([a-z0-9\-_.]+)@([a-z0-9\-.]+)$/i', $identifier, $matches)) {
            return false;
        }

        return [
            'name' => $matches[1],
            'domain' => $matches[2],
        ];
    }

    /**
     * Fetches and parses the .well-known/nostr.json file.
     *
     * @param string $domain The domain to fetch from
     * @param string $name The local part (name) to query for
     * @return array|null The parsed JSON data or null on failure
     */
    private function fetchJson(string $domain, string $name): ?array
    {
        $url = "https://{$domain}/.well-known/nostr.json?name={$name}";

        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'header' => 'Accept: application/json',
                'follow_location' => 0, // Disables automatic redirect following
                'timeout' => 10,
            ],
        ]);

        $response = @file_get_contents($url, false, $context);
        if ($response === false) {
            return null;
        }

        $data = json_decode($response, true);
        if (!is_array($data)) {
            return null;
        }

        return $data;
    }
}
