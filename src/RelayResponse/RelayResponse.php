<?php

declare(strict_types=1);

namespace swentel\nostr\RelayResponse;

use swentel\nostr\RelayResponseInterface;
use swentel\nostr\RelayResponse\RelayResponseOk;
use swentel\nostr\RelayResponse\RelayResponseEnum;

class RelayResponse implements RelayResponseInterface
{
    public string $type;

    public function __construct(array $response)
    {
        if (isset($response[0])) {
            $this->type = RelayResponseEnum::from($response[0])->value;
        } 
    }

    /**
     * Create a response object based on the given type using a match expression.
     *
     * @param array $response The response data to be used for creating the object.
     * @param string $type The type of response to determine which object to create.
     * @return object The created response object based on the type.
     */
    public static function createResponse(array $response, string $type)
    {
        return match ($type) {
            'ERROR' => new RelayResponseNotice($response),
            'EVENT' => new RelayResponseEvent($response),
            'OK' => new RelayResponseOk($response),
            'EOSE' => new RelayResponseEose($response),
            'CLOSED' => new RelayResponseClosed($response),
            'NOTICE' => new RelayResponseNotice($response),
            default => new self($response),
        };
    }

    /**
     * Static method to create a response object based on the given type using a match expression.
     *
     * @param array $response The response data to be used for creating the object.
     * @return object The created response object based on the determined type.
     */
    public static function create(array $response)
    {
        $type = RelayResponseEnum::from($response[0])->value;
        return self::createResponse($response, $type);
    }
}