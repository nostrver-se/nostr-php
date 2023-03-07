<?php

namespace swentel\nostr;

/**
 *
 */
class RelayResponse
{
    protected $success = FALSE;
    protected $reason = '';
    protected $event_id = '';

    public function __construct(array $response)
    {
        if ($response[0] == 'OK' && $response[2] && !str_starts_with($response[3], 'duplicate:')) {
            $this->success = TRUE;
            $this->event_id = $response[1];
        } else {
            $this->reason = !empty($resonse[3]) ?: 'Failed with no reason';
        }
    }

    public function isSuccess()
    {
        return $this->success === TRUE;
    }

    public function reason()
    {
        return $this->reason;
    }

    public function getEventId()
    {
        return $this->event_id;
    }
}
