<?php

declare(strict_types=1);

namespace swentel\nostr;

interface RelayResponseInterface
{
    /**
     * @param array $response
     * @param string $type
     * @return mixed
     */
    public static function createResponse(array $response, string $type): mixed;

    /**
     * @param array $response
     * @return mixed
     */
    public static function create(array $response): mixed;

    /**
     * Returns whether the request was successful.
     *
     * @return bool
     */
    public function isSuccess(): bool;
}
