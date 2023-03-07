<?php

namespace swentel\nostr;

use PHPUnit\Framework\TestCase;

class RelayTest extends TestCase
{
    /**
     * Tests publishing event to single relay.
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
        'content' => 'Hello from nostr-php',
      ];

      $signer = new Sign();
      $event = $signer->signEvent($event, $private_key);
      $message = $signer->generateEvent($event);

      $websocket = 'wss://nos.lol';
      $relay = new Relay($websocket);
      $result = $relay->publish($message);

      $expectedResult = [
          'OK',
          $event['id'],
          true,
          ''
      ];

      $this->assertEquals(
        $expectedResult,
        $result
      );

    }
}
