<?php

declare(strict_types=1);

namespace swentel\nostr;

interface RelayResponseInterface
{
    public static function createResponse(array $response, string $type);

    public static function create(array $response);
}
