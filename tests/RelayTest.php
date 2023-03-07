<?php
declare(strict_types=1);

namespace swentel\nostr;

use PHPUnit\Framework\TestCase;

class RelayTest extends TestCase
{
    /**
     * Tests publishing message to single relay.
     *
     */
    public function testRelayPublish()
    {
        $keys = new Keys();
        $private_key = $keys->generatePrivateKey();
        $public_key = $keys->getPublicKey($private_key);

        $event = [
          'pubkey' => $public_key,
          'created_at' => time(),
          'kind' => 1,
          'tags' => [],
          'content' => 'return_success_response',
        ];

        $signer = new Sign();
        $event = $signer->signEvent($event, $private_key);
        $message = $signer->generateEvent($event);

        $relay = new MockRelay();
        $result = $relay->publish($message);

        $this->assertTrue(
            $result->isSuccess()
          );

    }
}
