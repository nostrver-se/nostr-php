<?php

namespace swentel\nostr;

/**
 * Mocks Relay.
 *
 * @param String $message
 * @return RelayResponse|null
 */
class MockRelay implements RelayInterface
{
    public function publish($message)
    {
        $event = json_decode($message, true)[1];
        if ($event['content'] == 'return_success_response') {
            // Pass a 'success' response
            return new RelayResponse(['OK', $event['id'], TRUE, '']);
        }
    }
}
