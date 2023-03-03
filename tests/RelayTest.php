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
      //  preferring `envelope` over `message`, see https://github.com/jb55/nostril
      //  method name `generateEvent` may need to be refactored, otherwise the term `event` may be more confusing as it already is
      $envelope = $signer->generateEvent($event);

      $websocket = 'wss://nos.lol';
      $relay = new Relay($websocket);
      $result = $relay->publish($envelope);

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
