<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use swentel\nostr\Key\Key;

class GenerateTest extends TestCase
{
    /**
     * Tests getting public key from private key.
     */
    public function testGetPublicKey()
    {
        $keys = new Key();
        $private_key_hex = '67dea2ed018072d675f5415ecfaed7d2597555e202d85b3d65ea4e58d2d92ffa';
        $public_key_hex = '7e7e9c42a91bfef19fa929e5fda1b72e0ebc1a4c1141673e2794234d86addf4e';

        $this->assertEquals(
            $public_key_hex,
            $keys->getPublicKey($private_key_hex),
        );
    }
}
