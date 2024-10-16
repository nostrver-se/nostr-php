<?php

declare(strict_types=1);

namespace swentel\nostr\RelayResponse;

use swentel\nostr\RelayResponseInterface;

class RelayResponse implements RelayResponseInterface
{
    public string $type;

    public bool $isSuccess;

    public string $message;

    public function __construct(array $response)
    {
        $this->isSuccess = false;
        if (isset($response[0])) {
            $this->isSuccess = true;
            $this->type = RelayResponseEnum::from($response[0])->value;
            // Piece of legacy to support version <=1.3.3
            if ($this->type === 'OK' && $response[2] === false) {
                $this->isSuccess = false;
            }
            $this->message = !empty($response[3]) ? $response[3] : '';
        }
    }

    /**
     * Create a response object based on the given type using a match expression.
     *
     * @param array $response The response data to be used for creating the object.
     * @param string $type The type of response to determine which object to create.
     * @return object The created response object based on the type.
     */
    public static function createResponse(array $response, string $type): mixed
    {
        return match ($type) {
            'ERROR', 'NOTICE' => new RelayResponseNotice($response),
            'EVENT' => new RelayResponseEvent($response),
            'OK' => new RelayResponseOk($response),
            'EOSE' => new RelayResponseEose($response),
            'CLOSED' => new RelayResponseClosed($response),
            'AUTH' => new RelayResponseAuth($response),
            default => new self($response),
        };
    }

    /**
     * Static method to create a response object based on the given type using a match expression.
     *
     * @param array $response The response data to be used for creating the object.
     * @return object The created response object based on the determined type.
     */
    public static function create(array $response): mixed
    {
        $type = RelayResponseEnum::from($response[0])->value;
        return self::createResponse($response, $type);
    }

    /**
     *  Backwards compatability support for <=1.3.3 where we used the CommandResultInterface as a response.
     *
     * @return bool
     */
    public function isSuccess(): bool
    {
        return $this->isSuccess;
    }

    /**
     *  Backwards compatability support for <=1.3.3 where we used the CommandResultInterface as a response.
     *
     * @return string
     */
    public function message(): string
    {
        return $this->message;
    }
}
